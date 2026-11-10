SELECT id, status, exit_code, error, error_code, started_at, finished_at FROM heartbeat_runs ORDER BY started_at DESC LIMIT 5;
