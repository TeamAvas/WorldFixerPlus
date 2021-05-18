<?php

namespace skh6075\worldfixerplus\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use skh6075\worldfixerplus\Loader;

final class WorldFixerExecuteCommand extends Command{

    private Loader $plugin;

    public function __construct(Loader $plugin) {
        parent::__construct("wf", "world fixer execute command.");
        $this->setPermission("world.fixer.permission");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $player, string $label, array $args): bool{
        if (!$player instanceof Player or !$this->testPermission($player)) {
            return false;
        }

        if (!$this->plugin->canUseWorldFixer($player)) {
            $player->sendMessage(TextFormat::RED . "All corners must be touched to be used.");
            return false;
        }

        $this->plugin->onUseWorldFixer($player);
        return true;
    }
}