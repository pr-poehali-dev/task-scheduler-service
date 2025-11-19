const API_BASE = import.meta.env.VITE_API_URL || '/api';

export const BACKEND_URLS = {
  TELEGRAM_BOT: `${API_BASE}/telegram-webhook.php`,
  NOTIFY_TASK: `${API_BASE}/notify-task.php`,
  SAVE_TASK: `${API_BASE}/save-task.php`,
  SYNC_TASK: `${API_BASE}/sync-task.php`
} as const;