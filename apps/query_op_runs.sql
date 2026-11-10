SELECT id, agent_id, status, exit_code, error, error_code, started_at, finished_at FROM heartbeat_runs WHERE company_id = 'fcb854e2-4cff-4d8f-a0e3-587e5f1cfc8d' ORDER BY started_at DESC LIMIT 10;
