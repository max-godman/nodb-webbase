# Changelog

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
