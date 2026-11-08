<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AiAgent;
use App\Brain\Agents\AgentRunner;

class RunAiAgentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agent:run {agent? : Specific agent key (panchang, rashifal, seo_blog, festival, b2b, retention) or empty for all active} {--agent= : Specific agent key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute Online Puja AI autonomous agents';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $target = $this->argument('agent') ?: $this->option('agent');

        if ($target) {
            $this->info("🤖 Triggering agent: {$target}...");
            $res = AgentRunner::run($target);
            if ($res['ok']) {
                $this->info($res['message']);
            } else {
                $this->error($res['message']);
            }
            return 0;
        }

        $activeAgents = AiAgent::where('is_active', true)->get();
        $this->info("🚀 Running all active agents (" . $activeAgents->count() . " active)...");

        foreach ($activeAgents as $agent) {
            $this->line("• Running [{$agent->icon} {$agent->name}]...");
            $res = AgentRunner::run($agent->agent_key);
            if ($res['ok']) {
                $this->info("  ✓ {$res['message']}");
            } else {
                $this->warn("  ⚠️ {$res['message']}");
            }
        }

        $this->info("🎉 All agent cycles completed.");
        return 0;
    }
}
