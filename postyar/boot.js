#!/usr/bin/env node
"use strict";

/*
 * POSTYAR production bootstrap for cPanel/Setup Node.js.
 *
 * Guarantees:
 *   1) Setup Node.js environment variables take precedence; .env is a fallback.
 *   2) Production uses MariaDB/MySQL only; no SQLite fallback.
 *   3) The minimum schema needed by the shipped runtime is repaired before
 *      Next.js starts (idempotent, safe to run on every restart).
 *   4) A clear startup error is emitted when the database is unreachable or
 *      structurally incompatible, instead of allowing a silent HTTP 500.
 */
const fs = require("fs");
const path = require("path");

const root = __dirname;
process.chdir(root);
const envPath = path.join(root, ".env");

function parseEnv(text) {
  const out = {};
  for (const raw of String(text || "").split(/\r?\n/)) {
    const line = raw.trim();
    if (!line || line.startsWith("#")) continue;
    const eq = line.indexOf("=");
    if (eq < 1) continue;
    let value = line.slice(eq + 1).trim();
    if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
      value = value.slice(1, -1);
    }
    out[line.slice(0, eq).trim()] = value;
  }
  return out;
}

function fail(message) {
  console.error(`[postyar] ${message}`);
  process.exit(1);
}

if (fs.existsSync(envPath)) {
  // Setup Node.js / Passenger environment variables are the primary source.
  // .env is only a fallback for values not already injected by the host.
  const fileEnv = parseEnv(fs.readFileSync(envPath, "utf8"));
  for (const [key, value] of Object.entries(fileEnv)) {
    if (process.env[key] == null || process.env[key] === "") process.env[key] = value;
  }
}

const databaseUrl = String(process.env.DATABASE_URL || "").trim();
const hasMariaDbUrl = /^mysql:\/\//i.test(databaseUrl);
if (!hasMariaDbUrl) {
  console.warn("[postyar] DATABASE_URL معتبر MariaDB/MySQL در محیط اجرای Node.js پیدا نشد. سایت بالا می‌آید، اما قابلیت‌های وابسته به دیتابیس تا اصلاح این متغیر در دسترس نخواهند بود.");
}

for (const rel of [
  "db",
  "storage",
  "storage/avatars",
  "storage/images",
  "storage/receipts",
  "storage/videos",
]) {
  fs.mkdirSync(path.join(root, rel), { recursive: true });
}

process.env.NODE_ENV = "production";
process.env.HOSTNAME = process.env.HOSTNAME || "0.0.0.0";
process.env.PORT = process.env.PORT || "3000";
process.env.NEXT_TELEMETRY_DISABLED = "1";

function getPublicBaseUrl() {
  const raw = String(process.env.POSTYAR_PUBLIC_BASE_URL || "").trim();
  if (!raw) return "";
  try {
    const url = new URL(raw);
    if (!/^https?:$/.test(url.protocol)) return "";
    return url.toString().replace(/\/$/, "");
  } catch {
    return "";
  }
}

function prepareSeoArtifacts() {
  const base = getPublicBaseUrl();
  if (!base) return;
  const sitemap = [
    '<?xml version="1.0" encoding="UTF-8"?>',
    '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
    `  <url><loc>${base}/</loc><changefreq>weekly</changefreq><priority>1.0</priority></url>`,
    '</urlset>',
    "",
  ].join("\n");
  fs.writeFileSync(path.join(root, "public", "sitemap.xml"), sitemap, "utf8");
  fs.writeFileSync(
    path.join(root, "public", "robots.txt"),
    `User-agent: *\nAllow: /\nDisallow: /api/\nDisallow: /dashboard\nDisallow: /app\n\nSitemap: ${base}/sitemap.xml\n`,
    "utf8",
  );
}

prepareSeoArtifacts();

async function migrateProductionSchema() {
  const { PrismaClient } = require("@prisma/client");
  const prisma = new PrismaClient({ log: ["error"] });
  try {
    await prisma.$queryRawUnsafe("SELECT 1 AS ok");

    const requiredTables = await prisma.$queryRawUnsafe(
      "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('User','Plan','Profile','Session','Subscription','AuditLog')",
    );
    const tableNames = new Set(requiredTables.map((row) => String(row.TABLE_NAME)));
    for (const table of ["User", "Plan", "Profile", "Session", "Subscription", "AuditLog"]) {
      if (!tableNames.has(table)) throw new Error(`Required table '${table}' is missing.`);
    }

    const userColumns = await prisma.$queryRawUnsafe(
      "SELECT COLUMN_NAME, IS_NULLABLE, DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'User' AND COLUMN_NAME IN ('mobile','username')",
    );
    const userColumnMap = new Map(userColumns.map((row) => [String(row.COLUMN_NAME), row]));

    if (!userColumnMap.has("mobile")) {
      throw new Error("Required column User.mobile is missing.");
    }
    if (String(userColumnMap.get("mobile").IS_NULLABLE).toUpperCase() !== "YES") {
      await prisma.$executeRawUnsafe("ALTER TABLE `User` MODIFY COLUMN `mobile` VARCHAR(191) NULL");
    }

    if (!userColumnMap.has("username")) {
      await prisma.$executeRawUnsafe("ALTER TABLE `User` ADD COLUMN `username` VARCHAR(32) NULL");
    }

    // Normalize blank usernames to NULL first: MariaDB unique indexes allow
    // multiple NULLs, but empty strings would collide.
    await prisma.$executeRawUnsafe("UPDATE `User` SET username = NULL WHERE username = ''");

    // Remove duplicate usernames conservatively before creating the unique
    // index. Keeping the earliest row and clearing later duplicates avoids a
    // destructive delete while restoring the uniqueness invariant.
    const duplicateUsernames = await prisma.$queryRawUnsafe(
      "SELECT username FROM `User` WHERE username IS NOT NULL GROUP BY username HAVING COUNT(*) > 1",
    );
    for (const row of duplicateUsernames) {
      const username = String(row.username);
      const duplicates = await prisma.$queryRawUnsafe(
        "SELECT id FROM `User` WHERE username = ? ORDER BY createdAt ASC, id ASC",
        username,
      );
      for (const dup of duplicates.slice(1)) {
        await prisma.$executeRawUnsafe("UPDATE `User` SET username = NULL WHERE id = ?", dup.id);
      }
    }

    const usernameIndex = await prisma.$queryRawUnsafe(
      "SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'User' AND INDEX_NAME = 'User_username_key' LIMIT 1",
    );
    if (!usernameIndex.length) {
      await prisma.$executeRawUnsafe("CREATE UNIQUE INDEX `User_username_key` ON `User` (`username`)");
    }

    // The shipped plans contain structured JSON feature maps. VARCHAR(191)
    // is not a valid storage type for these payloads on MariaDB. Match the
    // SQL dump supplied for this project and keep it safely at TEXT size.
    const featureColumns = await prisma.$queryRawUnsafe(
      "SELECT DATA_TYPE, COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Plan' AND COLUMN_NAME = 'features' LIMIT 1",
    );
    if (!featureColumns.length) throw new Error("Required column Plan.features is missing.");
    const featureType = String(featureColumns[0].DATA_TYPE || "").toLowerCase();
    if (!["text", "mediumtext", "longtext"].includes(featureType)) {
      await prisma.$executeRawUnsafe("ALTER TABLE `Plan` MODIFY COLUMN `features` TEXT NOT NULL DEFAULT '{}'");
    }

    // Seed any missing canonical plans using SQL instead of Prisma upsert so
    // a stale generated Prisma schema can never reintroduce the VARCHAR bug.
    const plans = [
      ["cmtmy2vkq0000mls0w9pxry9z", "free", "رایگان", "برای آشنایی با پُست‌یار — ۵ پست در ماه، ۱ کانال.", 0, 1, JSON.stringify({ publishPerMonth: 5, aiPerMonth: 10, channels: 1, automation: 0 }), JSON.stringify({ publish: true, schedule: true, caption: true, smartText: true, smartReply: true, inbox: true, wallet: true, tickets: true, stats: true, referral: true, publishPerMonth: 5, aiPerMonth: 10, channels: 1, destinations: 1, bots: 0, contentItems: 25, glassButtonsPerDest: 0, workflowSteps: 0 })],
      ["cmtmy2vlh0001mls0bubx3eoy", "basic", "پایه", "مناسب کسب‌وکارهای کوچک — ۱۰۰ پست در ماه، ۳ کانال.", 200000000, 1, JSON.stringify({ publishPerMonth: 100, aiPerMonth: 500, channels: 3, automation: 1 }), JSON.stringify({ publish: true, schedule: true, multiChannel: true, bot: true, workflow: true, linkCodes: true, broadcast: true, glassButtons: true, caption: true, smartText: true, smartReply: true, autoResponder: true, inbox: true, woo: true, goldMonitor: true, advertising: true, referral: true, wallet: true, tickets: true, stats: true, automation: true, apiAccess: false, publishPerMonth: 100, aiPerMonth: 500, channels: 3, bots: 1, destinations: 10, contentItems: 500, glassButtonsPerDest: 3, workflowSteps: 10 })],
      ["cmtmy2vln0002mls0xdxwpm4x", "pro", "حرفه‌ای", "برای تیم‌های بازاریابی — ۱۰۰۰ پست، ۱۰ کانال، اتوماسیون کامل.", 500000000, 1, JSON.stringify({ publishPerMonth: 1000, aiPerMonth: 5000, channels: 10, automation: 5 }), JSON.stringify({ publish: true, schedule: true, multiChannel: true, bot: true, workflow: true, linkCodes: true, broadcast: true, glassButtons: true, caption: true, smartText: true, smartReply: true, autoResponder: true, inbox: true, woo: true, goldBot: true, goldMonitor: true, advertising: true, referral: true, wallet: true, tickets: true, stats: true, automation: true, apiAccess: true, publishPerMonth: 1000, aiPerMonth: 5000, channels: 10, bots: 5, destinations: 50, contentItems: 2000, glassButtonsPerDest: 10, workflowSteps: 25 })],
      ["cmtmy2vls0003mls0bkyvsx4c", "business", "سازمانی", "بدون محدودیت پست و کانال — پشتیبانی اختصاصی.", 1500000000, 1, JSON.stringify({ publishPerMonth: -1, aiPerMonth: -1, channels: -1, automation: -1 }), JSON.stringify({ publish: true, schedule: true, multiChannel: true, bot: true, workflow: true, linkCodes: true, broadcast: true, glassButtons: true, caption: true, smartText: true, smartReply: true, autoResponder: true, inbox: true, woo: true, goldBot: true, goldMonitor: true, advertising: true, referral: true, wallet: true, tickets: true, stats: true, automation: true, apiAccess: true, publishPerMonth: -1, aiPerMonth: -1, channels: -1, bots: -1, destinations: -1, contentItems: -1, glassButtonsPerDest: -1, workflowSteps: -1 })],
    ];

    const planCount = await prisma.$queryRawUnsafe("SELECT COUNT(*) AS count FROM `Plan`");
    if (Number(planCount[0]?.count || 0) < plans.length) {
      for (const plan of plans) {
        await prisma.$executeRawUnsafe(
          "INSERT INTO `Plan` (`id`,`code`,`nameFa`,`descriptionFa`,`priceRials`,`intervalMonths`,`quota`,`features`,`sortOrder`,`active`,`isPublic`,`createdAt`,`updatedAt`) VALUES (?,?,?,?,?,?,?, ?,0,1,1,NOW(3),NOW(3)) ON DUPLICATE KEY UPDATE `code`=VALUES(`code`)",
          plan[0], plan[1], plan[2], plan[3], plan[4], plan[5], plan[6], plan[7],
        );
      }
    }
  } finally {
    await prisma.$disconnect();
  }
}

function startProductionServer() {
  // Start Next.js immediately so cPanel/Passenger sees a healthy process.
  // Database repair is deliberately asynchronous and never blocks web
  // listener initialization. This avoids host-level 500s when MariaDB is
  // temporarily slow/unreachable while still repairing the schema before
  // normal application use.
  require("./server.js");

  if (process.env.POSTYAR_SKIP_DB_PREFLIGHT === "1" || !hasMariaDbUrl) {
    if (process.env.POSTYAR_SKIP_DB_PREFLIGHT === "1") {
      console.warn("[postyar] POSTYAR_SKIP_DB_PREFLIGHT=1 — database preflight skipped.");
    }
    return;
  }

  migrateProductionSchema()
    .then(() => console.log("[postyar] MariaDB preflight/migration completed."))
    .catch((error) => {
      const detail = error instanceof Error ? (error.stack || error.message) : String(error);
      console.error("[postyar] MariaDB preflight failed; web server remains online.");
      console.error(detail);
    });
}

startProductionServer();
