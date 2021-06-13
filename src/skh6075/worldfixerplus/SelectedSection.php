<?php

namespace skh6075\worldfixerplus;

use pocketmine\math\Vector3;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\Position;
use pocketmine\world\World;

final class SelectedSection{

    private World $world;

    private Vector3 $minPos;

    private Vector3 $maxPos;

    public function __construct(World $world) {
        $this->world = $world;
        $this->minPos = $this->maxPos = new Vector3(0, 0, 0);
    }

    public function selectPosition(Position $pos, bool $isMaxPos = true): ?string{
        if ($this->world->getFolderName() !== $pos->getWorld()->getFolderName()) {
            return null;
        }

        if ($isMaxPos) {
            $this->maxPos = $pos->asVector3();
        } else {
            $this->minPos = $pos->asVector3();
        }

        $selectNum = $isMaxPos ? 1 : 2;
        return "You have saved the " . $this->convertSelectType($selectNum) . " block. (x=" . $pos->getX() . ", y=" . $pos->getY() . ", z=" . $pos->getZ() . ")";
    }

    private function convertSelectType(int $selectNum): string{
        static $format = [
            1 => "first",
            2 => "second"
        ];
        return $format[$selectNum] ?? "null";
    }

    public function getMinPos(): Vector3{
        return new Vector3(...[
            min($this->minPos->getX(), $this->maxPos->getX()),
            min($this->minPos->getY(), $this->maxPos->getY()),
            min($this->minPos->getZ(), $this->maxPos->getZ())
        ]);
    }

    public function getMaxPos(): Vector3{
        return new Vector3(...[
            max($this->minPos->getX(), $this->maxPos->getX()),
            max($this->minPos->getY(), $this->maxPos->getY()),
            max($this->minPos->getZ(), $this->maxPos->getZ())
        ]);
    }

    public function toAsyncBinaries(): array{
        [$min, $max] = [$this->getMinPos(), $this->getMaxPos()];
        $chunks = [];
        for ($x = $min->getX(); $x < $max->getX(); $x ++) {
            for ($z = $min->getZ(); $z < $max->getZ(); $z ++) {
                $chunk = $this->world->getChunk($x >> 4, $z >> 4);
                $chunks[World::chunkHash($x >> 4, $z >> 4)] = FastChunkSerializer::serialize($chunk);
            }
        }

        return $chunks;
    }
}
