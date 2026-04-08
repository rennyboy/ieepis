-- IEEPIS Project — Neovim / LazyVim AI Context Hints
-- Place this file at the project root as .nvim.lua
-- It is auto-loaded by Neovim with exrc enabled: vim.opt.exrc = true

-- Project-level LSP hints
vim.lsp.config("intelephense", {
  settings = {
    intelephense = {
      environment = {
        phpVersion = "8.4.0"
      },
      files = {
        exclude = {
          "**/.git/**",
          "**/vendor/**",
          "**/node_modules/**",
          "**/storage/framework/**"
        }
      }
    }
  }
})

-- Project-level marks for key AI context files
-- Use :marks to view, or gm to jump to a mark
-- Example: 'A = AGENT.md entry point

-- Notify developer of AI context files on open
vim.notify(
  "IEEPIS Project Loaded\n" ..
  "AI Context → AGENT.md | ARCHITECTURE.md | DECISIONS.md\n" ..
  "Run commands via: vendor/bin/sail artisan ...",
  vim.log.levels.INFO,
  { title = "IEEPIS" }
)

-- Convenient terminal commands as user commands
vim.api.nvim_create_user_command("SailUp", function()
  vim.cmd("terminal vendor/bin/sail up -d")
end, { desc = "Start Laravel Sail" })

vim.api.nvim_create_user_command("SailTest", function()
  vim.cmd("terminal vendor/bin/sail artisan test --compact")
end, { desc = "Run all PHPUnit tests" })

vim.api.nvim_create_user_command("SailPint", function()
  vim.cmd("terminal vendor/bin/sail bin pint --dirty --format agent")
end, { desc = "Fix PHP code style with Pint" })

vim.api.nvim_create_user_command("SailMigrate", function()
  vim.cmd("terminal vendor/bin/sail artisan migrate")
end, { desc = "Run database migrations" })

vim.api.nvim_create_user_command("SailSeed", function()
  vim.cmd("terminal vendor/bin/sail artisan db:seed")
end, { desc = "Seed the database" })

vim.api.nvim_create_user_command("SailClear", function()
  vim.cmd("terminal vendor/bin/sail artisan optimize:clear")
end, { desc = "Clear all Laravel caches" })
