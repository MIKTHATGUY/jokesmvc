<?php
declare(strict_types=1);

namespace core;

class InPageLogger
{
    private static array $errors = [];
    private static bool $initialized = false;
    private static bool $hasOutput = false;

    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        if (!self::isDevMode()) {
            return;
        }

        self::$initialized = true;

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatal']);
    }

    private static function isDevMode(): bool
    {
        return \core\Config::get('app.APP_ENV', 'development') !== 'production';
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        self::$errors[] = [
            'type' => 'PHP Error',
            'severity' => self::getSeverityLabel($severity),
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'stack' => array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 1),
            'code_context' => self::getCodeContext($file, $line),
            'timestamp' => microtime(true),
        ];

        self::outputOverlay();
        return true;
    }

    public static function handleException(\Throwable $e): void
    {
        self::$errors[] = [
            'type' => get_class($e),
            'severity' => 'Fatal',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'stack' => self::formatStackTrace($e->getTrace()),
            'code_context' => self::getCodeContext($e->getFile(), $e->getLine()),
            'timestamp' => microtime(true),
        ];

        self::outputOverlay();

        if (!self::$hasOutput) {
            http_response_code(500);
        }
    }

    public static function handleFatal(): void
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            self::$errors[] = [
                'type' => 'Fatal Error',
                'severity' => 'Fatal',
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'stack' => [],
                'code_context' => self::getCodeContext($error['file'], $error['line']),
                'timestamp' => microtime(true),
            ];

            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: text/html; charset=utf-8');
            }

            self::outputOverlay();
        }
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        if (!self::isDevMode()) {
            return;
        }

        self::$errors[] = [
            'type' => 'Log',
            'severity' => strtoupper($level),
            'message' => $message,
            'file' => $context['file'] ?? 'manual',
            'line' => $context['line'] ?? 0,
            'stack' => array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 1),
            'code_context' => isset($context['file'], $context['line'])
                ? self::getCodeContext($context['file'], $context['line'])
                : null,
            'timestamp' => microtime(true),
        ];

        self::outputOverlay();
    }

    public static function getErrors(): array
    {
        return self::$errors;
    }

    public static function clear(): void
    {
        self::$errors = [];
    }

    public static function renderIndicator(): void
    {
        if (!self::isDevMode() || empty(self::$errors)) {
            return;
        }

        echo self::getOverlayHTML();
    }

    private static function outputOverlay(): void
    {
        self::hasOutputBeenSet();
        echo self::getOverlayHTML();
    }

    private static function hasOutputBeenSet(): void
    {
        self::$hasOutput = true;
    }

    private static function getSeverityLabel(int $severity): string
    {
        return match ($severity) {
            E_ERROR => 'Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse',
            E_NOTICE => 'Notice',
            E_DEPRECATED => 'Deprecated',
            E_STRICT => 'Strict',
            default => 'Unknown',
        };
    }

    private static function formatStackTrace(array $trace): array
    {
        $formatted = [];
        foreach ($trace as $i => $frame) {
            $formatted[] = [
                'file' => $frame['file'] ?? '[internal]',
                'line' => $frame['line'] ?? '?',
                'function' => ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? ''),
                'args' => $frame['args'] ?? [],
            ];
        }
        return $formatted;
    }

    private static function getCodeContext(string $file, int $line, int $padding = 5): ?array
    {
        if (!is_readable($file)) {
            return null;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return null;
        }

        $start = max(0, $line - $padding - 1);
        $end = min(count($lines), $line + $padding);
        $context = [];

        for ($i = $start; $i < $end; $i++) {
            $context[] = [
                'line_number' => $i + 1,
                'content' => $lines[$i] ?? '',
                'is_error_line' => ($i + 1) === $line,
            ];
        }

        return [
            'file' => $file,
            'lines' => $context,
        ];
    }

    private static function getOverlayHTML(): string
    {
        $errorCount = count(self::$errors);
        $hasErrors = $errorCount > 0;
        $errorsJson = json_encode(self::$errors, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES);
        $issueLabel = $errorCount !== 1 ? 'issues' : 'issue';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        return <<<HTML
<div id="nextjs-dev-overlay" data-errors='{$errorsJson}'>
  <style>
    #nextjs-dev-overlay {
      --overlay-bg: #1a1a2e;
      --overlay-border: #16213e;
      --overlay-text: #e0e0e0;
      --overlay-error: #e74c3c;
      --overlay-warning: #f39c12;
      --overlay-info: #3498db;
      --overlay-success: #2ecc71;
      --overlay-code-bg: #0d1117;
      --overlay-code-text: #c9d1d9;
      --overlay-highlight-bg: rgba(231, 76, 60, 0.3);
      --overlay-line-highlight: #ff7b72;
      --overlay-scrollbar-thumb: #30363d;
      --overlay-scrollbar-track: #161b22;
      font-family: 'JetBrains Mono', 'Fira Code', 'Cascadia Code', monospace;
    }

    #nextjs-dev-indicator {
      position: fixed;
      bottom: 12px;
      left: 12px;
      z-index: 999999;
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 8px 12px;
      background: var(--overlay-bg);
      border: 1px solid var(--overlay-border);
      border-radius: 8px;
      color: var(--overlay-text);
      font-size: 13px;
      font-weight: 500;
      cursor: pointer;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.4);
      transition: all 0.2s ease;
      user-select: none;
      -webkit-user-select: none;
    }

    #nextjs-dev-indicator:hover {
      background: #252542;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5);
    }

    #nextjs-dev-indicator .icon {
      width: 18px;
      height: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    #nextjs-dev-indicator .badge {
      background: var(--overlay-error);
      color: white;
      font-size: 11px;
      font-weight: 700;
      padding: 1px 6px;
      border-radius: 10px;
      min-width: 18px;
      text-align: center;
    }

    #nextjs-dev-overlay-panel {
      position: fixed;
      inset: 0;
      z-index: 999998;
      display: none;
      flex-direction: column;
      background: var(--overlay-bg);
      overflow: hidden;
    }

    #nextjs-dev-overlay-panel.visible {
      display: flex;
    }

    .dev-overlay-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 24px;
      background: #0f0f23;
      border-bottom: 1px solid var(--overlay-border);
      flex-shrink: 0;
    }

    .dev-overlay-header h2 {
      margin: 0;
      font-size: 16px;
      font-weight: 600;
      color: var(--overlay-text);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .dev-overlay-header h2 .error-count {
      background: var(--overlay-error);
      color: white;
      font-size: 12px;
      padding: 2px 8px;
      border-radius: 12px;
    }

    .dev-overlay-close {
      background: none;
      border: none;
      color: var(--overlay-text);
      font-size: 24px;
      cursor: pointer;
      padding: 4px 8px;
      border-radius: 4px;
      line-height: 1;
      transition: background 0.15s;
    }

    .dev-overlay-close:hover {
      background: rgba(255, 255, 255, 0.1);
    }

    .dev-overlay-content {
      flex: 1;
      overflow-y: auto;
      padding: 24px;
    }

    .dev-overlay-content::-webkit-scrollbar {
      width: 10px;
    }

    .dev-overlay-content::-webkit-scrollbar-track {
      background: var(--overlay-scrollbar-track);
    }

    .dev-overlay-content::-webkit-scrollbar-thumb {
      background: var(--overlay-scrollbar-thumb);
      border-radius: 5px;
    }

    .dev-overlay-content::-webkit-scrollbar-thumb:hover {
      background: #484f58;
    }

    .error-card {
      background: #161b22;
      border: 1px solid var(--overlay-border);
      border-radius: 12px;
      margin-bottom: 20px;
      overflow: hidden;
    }

    .error-card-header {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 16px 20px;
      background: rgba(231, 76, 60, 0.08);
      border-bottom: 1px solid var(--overlay-border);
    }

    .error-card-header.warning {
      background: rgba(243, 156, 18, 0.08);
    }

    .error-card-header.log {
      background: rgba(52, 152, 219, 0.08);
    }

    .error-icon {
      flex-shrink: 0;
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      font-size: 14px;
      font-weight: 700;
      margin-top: 2px;
    }

    .error-icon.error {
      background: var(--overlay-error);
      color: white;
    }

    .error-icon.warning {
      background: var(--overlay-warning);
      color: #000;
    }

    .error-icon.log {
      background: var(--overlay-info);
      color: white;
    }

    .error-title {
      flex: 1;
    }

    .error-title h3 {
      margin: 0 0 4px;
      font-size: 15px;
      font-weight: 600;
      color: var(--overlay-text);
    }

    .error-title .error-type {
      font-size: 12px;
      color: var(--overlay-error);
      font-weight: 500;
    }

    .error-title .error-type.warning {
      color: var(--overlay-warning);
    }

    .error-title .error-type.log {
      color: var(--overlay-info);
    }

    .error-file {
      font-size: 13px;
      color: #8b949e;
      margin-top: 4px;
    }

    .error-file a {
      color: #58a6ff;
      text-decoration: none;
    }

    .error-file a:hover {
      text-decoration: underline;
    }

    .code-frame {
      background: var(--overlay-code-bg);
      border-top: 1px solid var(--overlay-border);
      padding: 16px 20px;
      overflow-x: auto;
    }

    .code-frame::-webkit-scrollbar {
      height: 8px;
    }

    .code-frame::-webkit-scrollbar-track {
      background: var(--overlay-scrollbar-track);
    }

    .code-frame::-webkit-scrollbar-thumb {
      background: var(--overlay-scrollbar-thumb);
      border-radius: 4px;
    }

    .code-frame pre {
      margin: 0;
      font-size: 13px;
      line-height: 1.6;
      color: var(--overlay-code-text);
    }

    .code-frame .line {
      display: flex;
      padding: 0 8px;
      border-radius: 4px;
      margin: -2px -8px;
      transition: background 0.1s;
    }

    .code-frame .line.error-line {
      background: var(--overlay-highlight-bg);
      border-left: 3px solid var(--overlay-line-highlight);
      padding-left: 5px;
    }

    .code-frame .line:hover {
      background: rgba(255, 255, 255, 0.03);
    }

    .code-frame .line-number {
      color: #6e7681;
      min-width: 40px;
      text-align: right;
      padding-right: 16px;
      user-select: none;
      -webkit-user-select: none;
    }

    .code-frame .line-content {
      white-space: pre;
      font-family: inherit;
    }

    .stack-trace {
      border-top: 1px solid var(--overlay-border);
      padding: 16px 20px;
    }

    .stack-trace h4 {
      margin: 0 0 12px;
      font-size: 13px;
      font-weight: 600;
      color: #8b949e;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .stack-frame {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 8px 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      font-size: 13px;
    }

    .stack-frame:last-child {
      border-bottom: none;
    }

    .stack-frame-index {
      color: #6e7681;
      min-width: 24px;
      font-weight: 500;
    }

    .stack-frame-content {
      flex: 1;
      color: var(--overlay-code-text);
    }

    .stack-frame-content .function-name {
      color: #d2a8ff;
    }

    .stack-frame-content .file-path {
      color: #8b949e;
      font-size: 12px;
      margin-top: 2px;
    }

    .stack-frame-content .file-path a {
      color: #58a6ff;
      text-decoration: none;
    }

    .stack-frame-content .file-path a:hover {
      text-decoration: underline;
    }

    .dev-overlay-footer {
      padding: 12px 24px;
      background: #0f0f23;
      border-top: 1px solid var(--overlay-border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-shrink: 0;
    }

    .dev-overlay-footer span {
      font-size: 12px;
      color: #8b949e;
    }

    .dev-overlay-footer button {
      background: none;
      border: 1px solid var(--overlay-border);
      color: var(--overlay-text);
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 12px;
      cursor: pointer;
      transition: all 0.15s;
    }

    .dev-overlay-footer button:hover {
      background: rgba(255, 255, 255, 0.1);
    }

    @media (max-width: 768px) {
      #nextjs-dev-indicator {
        bottom: 8px;
        left: 8px;
        padding: 6px 10px;
        font-size: 12px;
      }

      .dev-overlay-header,
      .dev-overlay-content,
      .dev-overlay-footer {
        padding-left: 16px;
        padding-right: 16px;
      }

      .code-frame pre {
        font-size: 12px;
      }
    }
  </style>

  <div id="nextjs-dev-indicator" onclick="toggleDevOverlay()">
    <span class="icon">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 2L2 19.5H22L12 2Z" fill="#e74c3c" stroke="#e74c3c" stroke-width="1" stroke-linejoin="round"/>
        <text x="12" y="15" text-anchor="middle" fill="white" font-size="8" font-weight="bold">!</text>
      </svg>
    </span>
    <span>{$errorCount} {$issueLabel}</span>
    <span class="badge">{$errorCount}</span>
  </div>

  <div id="nextjs-dev-overlay-panel">
    <div class="dev-overlay-header">
      <h2>
        Development Errors
        <span class="error-count">{$errorCount}</span>
      </h2>
      <button class="dev-overlay-close" onclick="toggleDevOverlay()">&times;</button>
    </div>
    <div class="dev-overlay-content" id="dev-overlay-content">
    </div>
    <div class="dev-overlay-footer">
      <span>PHP MVC Dev Overlay • {$requestMethod} {$requestUri}</span>
      <button onclick="clearDevErrors()">Clear All</button>
    </div>
  </div>

  <script>
    (function() {
      window.devErrors = JSON.parse(document.getElementById('nextjs-dev-overlay').getAttribute('data-errors') || '[]');

      function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
      }

      function renderErrors() {
        const container = document.getElementById('dev-overlay-content');
        if (!container) return;

        if (window.devErrors.length === 0) {
          container.innerHTML = '<p style="color: #8b949e; text-align: center; padding: 40px;">No errors to display.</p>';
          return;
        }

        let html = '';
        window.devErrors.forEach(function(error, index) {
          const severityClass = error.severity.toLowerCase() === 'warning' || error.severity.toLowerCase() === 'notice' || error.severity.toLowerCase() === 'deprecated' ? 'warning' : (error.type === 'Log' ? 'log' : 'error');

          html += '<div class="error-card">';
          html += '<div class="error-card-header ' + severityClass + '">';
          html += '<div class="error-icon ' + severityClass + '">' + (severityClass === 'error' ? '✕' : (severityClass === 'warning' ? '!' : 'i')) + '</div>';
          html += '<div class="error-title">';
          html += '<h3>' + escapeHtml(error.message) + '</h3>';
          html += '<span class="error-type ' + severityClass + '">' + escapeHtml(error.type) + ' • ' + escapeHtml(error.severity) + '</span>';
          html += '<div class="error-file">';
          html += '<a href="#">' + escapeHtml(error.file) + ':' + error.line + '</a>';
          html += '</div>';
          html += '</div>';
          html += '</div>';

          if (error.code_context && error.code_context.lines) {
            html += '<div class="code-frame"><pre>';
            error.code_context.lines.forEach(function(line) {
              const lineClass = line.is_error_line ? 'line error-line' : 'line';
              html += '<div class="' + lineClass + '">';
              html += '<span class="line-number">' + line.line_number + '</span>';
              html += '<span class="line-content">' + escapeHtml(line.content) + '</span>';
              html += '</div>';
            });
            html += '</pre></div>';
          }

          if (error.stack && error.stack.length > 0) {
            html += '<div class="stack-trace">';
            html += '<h4>Stack Trace</h4>';
            error.stack.forEach(function(frame, i) {
              html += '<div class="stack-frame">';
              html += '<span class="stack-frame-index">' + i + '</span>';
              html += '<div class="stack-frame-content">';
              html += '<span class="function-name">' + escapeHtml(frame.function || '[anonymous]') + '</span>';
              if (frame.file && frame.file !== '[internal]') {
                html += '<div class="file-path"><a href="#">' + escapeHtml(frame.file) + ':' + frame.line + '</a></div>';
              }
              html += '</div>';
              html += '</div>';
            });
            html += '</div>';
          }

          html += '</div>';
        });

        container.innerHTML = html;
      }

      window.toggleDevOverlay = function() {
        const panel = document.getElementById('nextjs-dev-overlay-panel');
        if (panel) {
          panel.classList.toggle('visible');
        }
      };

      window.clearDevErrors = function() {
        window.devErrors = [];
        renderErrors();
        const indicator = document.getElementById('nextjs-dev-indicator');
        if (indicator) {
          indicator.style.display = 'none';
        }
      };

      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          const panel = document.getElementById('nextjs-dev-overlay-panel');
          if (panel && panel.classList.contains('visible')) {
            panel.classList.remove('visible');
          }
        }
      });

      renderErrors();
    })();
  </script>
</div>
HTML;
    }
}
