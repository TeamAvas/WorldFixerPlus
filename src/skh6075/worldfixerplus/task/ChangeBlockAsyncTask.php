<?php

namespace skh6075\worldfixerplus\task;

use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\World;

final class ChangeBlockAsyncTask extends AsyncTask{

    private const REPLACED_BLOCK = [
        158 => [BlockLegacyIds::WOODEN_SLAB, 0],
        125 => [BlockLegacyIds::DOUBLE_WOODEN_SLAB, 0],
        188 => [BlockLegacyIds::FENCE, 0],
        189 => [BlockLegacyIds::FENCE, 1],
        190 => [BlockLegacyIds::FENCE, 2],
        191 => [BlockLegacyIds::FENCE, 3],
        192 => [BlockLegacyIds::FENCE, 4],
        193 => [BlockLegacyIds::FENCE, 5],
        166 => [BlockLegacyIds::INVISIBLE_BEDROCK, 0],
        144 => [BlockLegacyIds::AIR, 0],
        208 => [BlockLegacyIds::GRASS_PATH, 0],
        198 => [BlockLegacyIds::END_ROD, 0],
        126 => [BlockLegacyIds::WOODEN_SLAB, 0],
        95 => [BlockLegacyIds::STAINED_GLASS, null],
        199 => [BlockLegacyIds::CHORUS_PLANT, 0],
        202 => [BlockLegacyIds::PURPUR_BLOCK, 0],
        251 => [BlockLegacyIds::CONCRETE, 0],
        204 => [BlockLegacyIds::PURPUR_BLOCK, 0]
    ];

    private string $playerName;

    /** @var string[] */
    private array $chunks;

    private Vector3 $startPos;

    private Vector3 $endPos;

    private float $startTime;

    private float $endTime;

    private int $totalCount;

    public function __construct(string $playerName, array $chunks, Vector3 $startPos, Vector3 $endPos) {
        $this->playerName = $playerName;
        $this->chunks = $chunks;
        $this->startPos = $startPos;
        $this->endPos = $endPos;
        $this->startTime = microtime(true);
    }

    public function onRun(): void{
        $chunks = (array) $this->chunks;
        foreach ($chunks as $hash => $data) {
            $chunks[$hash] = FastChunkSerializer::deserialize($data);
        }

        $totalCount = 0;
        for ($x = $this->startPos->getX(); $x < $this->endPos->getX(); $x ++) {
            for ($z = $this->startPos->getZ(); $z < $this->endPos->getZ(); $z ++) {
                $hash = World::chunkHash($x >> 4, $z >> 4);
                if (!($chunk = $chunks[$hash] ?? null) instanceof Chunk)
                    continue;

                for ($y = $this->startPos->getY(); $y < $this->endPos->getY(); $y ++) {
                    $subChunk = $chunk->getSubChunk($y);
                    $fullBlock = $subChunk->getFullBlock($x & 0xf, $y & 0xf, $z & 0xf);
                    $id = $fullBlock >> 4;
                    $meta = $fullBlock & 0xf;
                    if (!in_array($id, array_keys(self::REPLACED_BLOCK)))
                        continue;

                    $data = self::REPLACED_BLOCK[$id];
                    $newBlock = BlockFactory::getInstance()->get($data[0], $data[1] ?? $meta);
                    $subChunk->setFullBlock($x & 0xf, $y & 0xf, $z & 0xf, $newBlock->getId() << 4 | $newBlock->getMeta());
                    ++ $totalCount;
                }
            }
        }

        $this->totalCount = $totalCount;
        $this->endTime = microtime(true) - $this->startTime;
    }

    public function onCompletion(): void{
        if (($player = Server::getInstance()->getPlayerExact($this->playerName)) instanceof Player) {
            $player->sendMessage("All blocks have been replaced. [time: " . round($this->endTime, 3) . ", changedBlocks: " . $this->totalCount . "]");
        }
    }
}
