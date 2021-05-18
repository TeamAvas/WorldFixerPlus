<?php

namespace skh6075\worldfixerplus;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use skh6075\worldfixerplus\command\WorldFixerExecuteCommand;
use skh6075\worldfixerplus\task\ChnageBlockAsyncTask;

final class Loader extends PluginBase implements Listener{
    use SingletonTrait;

    /** @var SelectedSection[] */
    private static array $selectedSection = [];

    protected function onLoad(): void{
        self::setInstance($this);
    }

    protected function onEnable(): void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), new WorldFixerExecuteCommand($this));
    }

    private function getWorldFixerTool(Player $player): ?SelectedSection{
        return self::$selectedSection[spl_object_hash($player)] ?? null;
    }

    public function canUseWorldFixer(Player $player): bool{
        if (!($fixer = $this->getWorldFixerTool($player)) instanceof SelectedSection) {
            return false;
        }

        return !$fixer->getMaxPos()->equals($fixer->getMinPos());
    }

    public function onUseWorldFixer(Player $player): void{
        $fixer = $this->getWorldFixerTool($player);
        $this->getServer()->getAsyncPool()->submitTask(new ChnageBlockAsyncTask($player->getName(), $fixer->toAsyncBinaries(), $fixer->getWorld(), $fixer->getMinPos(), $fixer->getMaxPos()));
    }

    public function onPlayerInteract(PlayerInteractEvent $event): void{
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if ($event->getItem()->getId() !== ItemIds::GOLD_AXE and !$player->isCreative())
            return;

        if (!$this->getWorldFixerTool($player) instanceof SelectedSection) {
            self::$selectedSection[spl_object_hash($player)] = new SelectedSection($block->getPos()->getWorld());
        }

        $fixerTool = $this->getWorldFixerTool($player);
        if (($result = $fixerTool->selectPosition($block->getPos(), true)) !== null) {
            $player->sendMessage($result);
        }

        $event->cancel();
    }

    public function onBlockBreak(BlockBreakEvent $event): void{
        $player = $event->getPlayer();
        $block = $event->getBlock();
        if ($event->getItem()->getId() !== ItemIds::GOLD_AXE and !$player->isCreative())
            return;

        if (!$this->getWorldFixerTool($player) instanceof SelectedSection) {
            self::$selectedSection[spl_object_hash($player)] = new SelectedSection($block->getPos()->getWorld());
        }

        $fixerTool = $this->getWorldFixerTool($player);
        if (($result = $fixerTool->selectPosition($block->getPos(), false)) !== null) {
            $player->sendMessage($result);
        }

        $event->cancel();
    }
}