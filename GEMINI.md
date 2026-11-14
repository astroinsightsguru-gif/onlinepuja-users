# Antigravity + OmniRoute Hybrid Execution Rule

## Quota Optimization & Delegation Policy
1. **Local Antigravity Role (Orchestrator)**:
   - Handle user interaction, git commands, terminal verification, and filesystem operations (`view_file`, `write_to_file`, `replace_file_content`).
   - Keep orchestrator responses concise to minimize token burn.

2. **OmniRoute Role (Generator & Cognitive Engine)**:
   - For any complex reasoning, architectural design, debugging analysis, and heavy code generation, Antigravity MUST invoke the `omniroute_chat` tool.
   - Default to `auto/coding:free` or `auto/best-free` on the OmniRoute gateway (`ai.vmstudio.digital`) to ensure zero Antigravity quota consumption.
   - When external LLMs return code/patches, the Antigravity agent inspects and applies them directly to the repository files.
