# Changelog

## [0.9.0] - 2026-05-12

### Major: Routing & Page Management Rewrite

**Core changes:**
- Abandoned fixed handler functions (`getTagPage()`, `getArticlePage()`, etc.) — replaced with 4 universal page types: `page`, `code`, `paged`, `api`
- URL Pattern auto-compilation — admin enters `/tag/{abc}`, system generates regex `~^/tag/([^/]+)$~` with vars `['abc']`
- Code blocks stored as standalone files (`tpl/code_{key}.log`), edited via File Editor
- `{code:xxx}` placeholder system — code block variables auto-exposed for Content editor reference
- Route status changed to 3 states: 2=Active, 1=Paused, 0=Delete
- Single Save button (no more Save Draft + Activate two-step)
- Unified page editor — all 4 types managed in one place

**New files:**
- `tpl/code_tag.log`, `tpl/code_search.log`, `tpl/code_article.log`, `tpl/code_category.log`, `tpl/code_news.log`, `tpl/code_info.log` — Auto-generated code block files for existing dynamic pages

**Modified files:**
- `inc/site_functions.php` — Added `compileRoutePattern()`, updated `matchRoute()` with `named_params`, removed all `get*Page()` functions and `renderDynamicTemplate()`
- `inc/site_router.php` — Simplified to `['match', 'key', 'vars']` format
- `inc/site_pages.php` — Unified format with `type` field, removed `path`/`before_code`
- `index.php` — New dispatch: API→include code file+exit, code/paged→include code+`{code:xxx}` replacement
- `adm/router.php` — Complete rewrite: URL Pattern input, 3-state status dropdown, single Save button, auto-compile
- `adm/pages.php` — Rewritten as pure Content editor, read-only code block display
- `adm/nav.php` — Tab label changed to "Menu"
- `adm/inc_menu.log` — Updated menu entries: Pages/Content/Menu

**Renamed files:**
- `inc/site_dynamic.php` → `-inc_site_dynamic.php` (functionality merged into site_pages.php)

**Infrastructure:**
- `inc/sys_sql.php` — Created as config template (empty params, setup.php writes on install)
- `inc/sys_conn.php` — Created to auto-read sys_sql.php, setup.php creates once, not rewritten

### Additional v0.9.0 Changes

**New files:**
- `tpl/adm_msg.log` — Announcement template, editable via File Editor, displayed in msg.php
- `tpl/code_post.log`, `tpl/code_sub.log` — Code/API examples for new default routes

**Modified files:**
- `adm/msg.php` — Announcements card below Quick Links, reads from `tpl/adm_msg.log`
- `adm/nav.php` — Preview link for active items; hint for `{sys_site_weburl}` absolute URLs
- `adm/router.php` — Preview link (↗) for Active routes
- `adm/pages.php` — Type badge clickable as preview link for Active pages
- `setup.php` — Password hint shows live `getTdayShort()`; Database Config hides without `data/sql.log`; domain field warns against http/www
- `test.php` — Added `getTdayShort()` to Date Formats
- `inc/sys_inc.php` — Added `ico` support, skips `getimagesize()` for ICO
- `adm/sys.php` — Added `ico` to upload/delete whitelist
- `data/site_router.log` — Default routes: index→Paused, removed category/news/info, tag→paged format, added post/sub
- `inc/site_router.php` — Recompiled (only about + terms active)
- `inc/site_pages.php` — Removed category/news/info, added post/sub
- `data/editor_files.log` — Synced with current code files

**Deleted files:**
- `tpl/code_category.log`, `tpl/code_news.log`, `tpl/code_info.log` — Removed with their routes

### Migration notes for existing installations:

1. `data/site_router.log` — Format changed from 6-field to 5-field (`status|pattern|key|vars|remark`)
2. `inc/site_pages.php` — Each entry needs `type` field; remove `path` field
3. `inc/site_dynamic.php` content merged into `inc/site_pages.php`
4. Placeholders: `{tagname}` → `{code:tagname}`, `{content}` → `{code:content_html}`
5. Old handler function code must be migrated into `tpl/code_{key}.log` files
6. See `ROADMAP.md` §7 for detailed migration steps

### 重大变更：路由与页面管理重构

**核心变化：**
- 废弃固定 handler 函数（`getTagPage()` 等），改用 4 种通用页面类型：`page`/`code`/`paged`/`api`
- URL Pattern 自动编译：管理员输入 `/tag/{abc}`，系统自动生成正则 + 变量名
- 代码块独立文件存储（`tpl/code_{key}.log`），通过 File Editor 编辑
- `{code:xxx}` 占位符体系：代码块变量自动暴露给 Content 编辑器引用
- 路由状态改为 3 档：2=启用、1=暂停、0=删除
- 单按钮保存（不再分保存草稿 + 生效两步）
- 统一页面编辑器：4 种类型在一个界面管理

**新增文件：**
- 6 个 `tpl/code_*.log` — 现有动态页面的代码块文件

**修改文件：**
- `inc/site_functions.php` — 加 `compileRoutePattern()`，改 `matchRoute()` 支持 `named_params`，删所有 getPage 函数
- `inc/site_router.php` — 简化为 `['match', 'key', 'vars']`
- `inc/site_pages.php` — 统一格式，加 type 字段
- `index.php` — 新分发流程
- `adm/router.php` — 完全重写
- `adm/pages.php` — 重写为纯内容编辑器
- `adm/nav.php` — Tab 改为 Menu

**重命名文件：**
- `inc/site_dynamic.php` → `-inc_site_dynamic.php`

### Additional v0.9.0 Changes

**New files:**
- `tpl/adm_msg.log` — Announcement template, editable via File Editor, displayed in msg.php
- `tpl/code_post.log` — Code example for POST form handling
- `tpl/code_sub.log` — API example for JSON endpoint

**Modified files:**
- `adm/msg.php` — Added Announcements card below Quick Links, reads from `tpl/adm_msg.log`
- `adm/nav.php` — Added preview link for active items; hint for `{sys_site_weburl}` absolute URLs
- `adm/router.php` — Added preview link (↗) for Active routes
- `adm/pages.php` — Type badge now clickable preview link for Active pages
- `setup.php` — Password hint shows live `getTdayShort()` value; domain field warns against http/www prefix; Database Config section hidden when no `data/sql.log`
- `test.php` — Added `getTdayShort()` display in Date Formats section
- `inc/sys_inc.php` — Added `ico` to allowed image extensions, skips `getimagesize()` for ICO
- `adm/sys.php` — Added `ico` to image upload/delete extension whitelist
- `data/site_router.log` — Default routes: index→Paused, removed category/news/info, tag→paged `/tag/{abc}/{page}.html`, added post/sub examples
- `inc/site_router.php` — Recompiled (only about + terms active)
- `inc/site_pages.php` — Removed category/news/info, added post/sub
- `data/editor_files.log` — Synced with current code files

**Deleted files:**
- `tpl/code_category.log`, `tpl/code_news.log`, `tpl/code_info.log` — Removed with their routes

## [0.8.4] - 2026-05-11

### Changed Files

- **`inc/sys_inc.php`**
  - Added `uploadImage()` function — Reusable image upload helper with extension, size, and dimension validation.
  - `checkAntiRefresh()` changed to millisecond precision — `time()` → `microtime(true) * 1000`, default interval 1000ms (1s). Error message shows decimal seconds remaining.
  - `showErrorPage()` auto return URL — When `$returnUrl` is empty, defaults to `$_SERVER['REQUEST_URI']` so error pages always show a Refresh link.

- **`adm/router.php`**
  - Fixed Add Static Route button not submitting action — Missing `name="router_action" value="save"` caused new route data to be posted but never processed. Added the missing attributes.
  - Auto-create page entry on Add Static Route — When adding a static route, if the page key doesn't exist in `inc/site_pages.php`, a default entry is automatically created.

- **`adm/pages.php`**
  - Fixed Add New Page wipes all existing pages — The Add form is a separate `<form>` that doesn't include existing page keys. Fixed: when `page_key[]` is empty, load existing pages from file before merging.

- **`adm/sys.php`**
  - New `type=pics` tab — Image upload to `pics/` directory with custom filename, max 10MB, 10-5000px dimensions, supports jpg/jpeg/png/gif/webp/bmp/svg. Image list with thumbnail preview, modified time, open link, and remove button.

- **`test.php`**
  - New Image Upload Environment check section — Tests `getimagesize()`, `file_uploads`, `upload_max_filesize`, `post_max_size`, `pics/` writability, `move_uploaded_file()`.
  - Updated `checkAntiRefresh()` call to 1000ms.

- **`README.md`**
  - test.php description updated — "Test playground" → "System environment checker & function test". Chinese version synced.

### 变更文件

- **`inc/sys_inc.php`**
  - 新增 `uploadImage()` 函数 — 图片上传辅助函数，含扩展名、大小、尺寸校验。
  - `checkAntiRefresh()` 毫秒精度 — 从 `time()`（秒）改为 `microtime(true) * 1000`（毫秒），默认 1000ms（1秒）。错误信息显示小数秒。
  - `showErrorPage()` 自动返回链接 — `$returnUrl` 为空时自动用 `$_SERVER['REQUEST_URI']`，错误页始终显示 Refresh 链接。

- **`adm/router.php`**
  - 修复 Add 按钮未提交 action — 缺少 `name="router_action" value="save"`，导致新路由数据提交后未处理。已补全属性。
  - 添加路由时自动创建页面条目 — 新增静态路由时，自动检查 `inc/site_pages.php` 是否有对应 key，没有则创建默认条目。

- **`adm/pages.php`**
  - 修复 Add New Page 丢失已有页面 — Add 表单不包含已有页面字段，修复：`page_key[]` 为空时先加载已有文件再合并。

- **`adm/sys.php`**
  - 新增 `type=pics` 标签页 — 上传图片到 `pics/` 目录，支持自定义文件名、最大10MB、10-5000px、jpg/jpeg/png/gif/webp/bmp/svg。图片列表带缩略图、修改时间、打开链接和删除按钮。

- **`test.php`**
  - 新增 Image Upload Environment 检测区块 — 检测 `getimagesize()`、`file_uploads`、上传大小限制、`pics/` 可写性等运行环境。
  - 更新 `checkAntiRefresh()` 调用为 1000ms。

- **`README.md`**
  - test.php 描述更新 — "Test playground" → "System environment checker & function test"，中文同步。
