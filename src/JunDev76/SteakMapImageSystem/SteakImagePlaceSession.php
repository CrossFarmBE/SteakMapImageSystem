<?php

/**
 * ImageOnMap - Easy to use PocketMine plugin, which allows loading images on maps
 * Copyright (C) 2021 - 2022 CzechPMDevs
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace JunDev76\SteakMapImageSystem;

use czechpmdevs\imageonmap\ImageOnMap;
use czechpmdevs\imageonmap\item\FilledMap;
use czechpmdevs\imageonmap\utils\PermissionDeniedException;
use pocketmine\block\Block;
use pocketmine\block\ItemFrame;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\World;

use function max;
use function min;

class SteakImagePlaceSession implements Listener{

    protected ImageOnMap $plugin;

    public function __construct(public string $imageFile, public Vector3 $firstPosition, public Vector3 $secondPosition, public World $world){
        $this->plugin = ImageOnMap::getInstance();

        $this->finish();
    }

    private function finish() : void{
        /** @var int $minX */
        $minX = min($this->firstPosition->getX(), $this->secondPosition->getX());
        /** @var int $maxX */
        $maxX = max($this->firstPosition->getX(), $this->secondPosition->getX());

        /** @var int $minY */
        $minY = min($this->firstPosition->getY(), $this->secondPosition->getY());
        /** @var int $maxY */
        $maxY = max($this->firstPosition->getY(), $this->secondPosition->getY());

        /** @var int $minZ */
        $minZ = min($this->firstPosition->getZ(), $this->secondPosition->getZ());
        /** @var int $maxZ */
        $maxZ = max($this->firstPosition->getZ(), $this->secondPosition->getZ());

        $world = $this->world;

        $itemFrame = VanillaBlocks::ITEM_FRAME();
        if($minX === $maxX){
            // West x East
            if($world->getBlock($this->firstPosition->add(1, 0, 0), true, false)->isSolid()){
                $itemFrame->setFacing(Facing::WEST);
            }else{
                $itemFrame->setFacing(Facing::EAST);
            }
        }else{
            // North x South
            if($world->getBlock($this->firstPosition->add(0, 0, 1), true, false)->isSolid()){
                $itemFrame->setFacing(Facing::NORTH);
            }else{
                $itemFrame->setFacing(Facing::SOUTH);
            }
        }

        $getItemFrame = static function(int $x, int $y, int $z) use ($itemFrame, $world) : ItemFrame{
            $block = $world->getBlockAt($x, $y, $z, true, false);
            if($block instanceof ItemFrame){
                return $block;
            }

            $world->setBlockAt($x, $y, $z, $itemFrame);
            $block = $world->getBlockAt($x, $y, $z, true, false);
            if(!$block instanceof ItemFrame){
                throw new AssumptionFailedError("Block must be item frame");
            }

            return $block;
        };

        /** @var ItemFrame $pattern */
        $pattern = $world->getBlock($this->secondPosition);

        if(!$pattern instanceof ItemFrame){
            return;
        }

        /** @var Block[] $blocks */
        $blocks = [];

        try{
            $height = $maxY - $minY;
            if($minX === $maxX){
                $width = $maxZ - $minZ;
                if($pattern->getFacing() === Facing::WEST){
                    for($x = 0; $x <= $width; ++$x){
                        for($y = 0; $y <= $height; ++$y){
                            $blocks[] = $getItemFrame($minX, $minY + $y, $minZ + $x)->setFramedItem(FilledMap::get()->setMapId($this->plugin->getImageFromFile($this->imageFile, $width + 1, $height + 1, $x, $height - $y)))->setHasMap(true);
                        }
                    }
                }else{
                    for($x = 0; $x <= $width; ++$x){
                        for($y = 0; $y <= $height; ++$y){
                            $blocks[] = $getItemFrame($minX, $minY + $y, $maxZ - $x)->setFramedItem(FilledMap::get()->setMapId($this->plugin->getImageFromFile($this->imageFile, $width + 1, $height + 1, $x, $height - $y)))->setHasMap(true);
                        }
                    }
                }
            }else{
                $width = $maxX - $minX;
                if($pattern->getFacing() === Facing::SOUTH){
                    for($x = 0; $x <= $width; ++$x){
                        for($y = 0; $y <= $height; ++$y){
                            $blocks[] = $getItemFrame($minX + $x, $minY + $y, $minZ)->setFramedItem(FilledMap::get()->setMapId($this->plugin->getImageFromFile($this->imageFile, $width + 1, $height + 1, $x, $height - $y)))->setHasMap(true);
                        }
                    }
                }else{
                    for($x = 0; $x <= $width; ++$x){
                        for($y = 0; $y <= $height; ++$y){
                            $blocks[] = $getItemFrame($maxX - $x, $minY + $y, $minZ)->setFramedItem(FilledMap::get()->setMapId($this->plugin->getImageFromFile($this->imageFile, $width + 1, $height + 1, $x, $height - $y)))->setHasMap(true);
                        }
                    }
                }
            }

            foreach($blocks as $block){
                $world->setBlock($block->getPosition(), $block);
            }
        }catch(PermissionDeniedException){
        }
    }
}