<?php

/**
 *
 * MMP""MM""YMM               .M"""bgd
 * P'   MM   `7              ,MI    "Y
 *      MM  .gP"Ya   ,6"Yb.  `MMb.   `7MMpdMAo.  ,pW"Wq.   ,pW"Wq.`7MMpMMMb.
 *      MM ,M'   Yb 8)   MM    `YMMNq. MM   `Wb 6W'   `Wb 6W'   `Wb MM    MM
 *      MM 8M""""""  ,pm9MM  .     `MM MM    M8 8M     M8 8M     M8 MM    MM
 *      MM YM.    , 8M   MM  Mb     dM MM   ,AP YA.   ,A9 YA.   ,A9 MM    MM
 *    .JMML.`Mbmmd' `Moo9^Yo.P"Ybmmd"  MMbmmd'   `Ybmd9'   `Ybmd9'.JMML  JMML.
 *                                     MM
 *                                   .JMML.
 * This file is part of TeaSpoon.
 *
 * TeaSpoon is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TeaSpoon is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with TeaSpoon.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author CortexPE
 * @link https://CortexPE.xyz
 *
 */

declare(strict_types = 1);

namespace CortexPE\item;

use CortexPE\entity\projectile\FishingHook;
use CortexPE\Main;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Item;
use pocketmine\item\ProjectileItem;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\Player;

class FishingRod extends ProjectileItem {
	public function __construct($meta = 0, $count = 1){
		parent::__construct(Item::FISHING_ROD, $meta, "Fishing Rod");
	}

	public function getMaxStackSize(): int{
		return 1;
	}

	public function getMaxDurability(){
		return 65;
	}

	public function onClickAir(Player $player, Vector3 $directionVector): bool{
		$session = Main::getInstance()->getSessionById($player->getId());
		if(!$session->fishing){
			$nbt = Entity::createBaseNBT($player->add(0, $player->getEyeHeight(), 0), $directionVector, $player->yaw, $player->pitch);

			$projectile = Entity::createEntity($this->getProjectileEntityType(), $player->getLevel(), $nbt, $player);
			if($projectile !== null){
				$projectile->setMotion($projectile->getMotion()->multiply($this->getThrowForce()));
			}

			if($projectile instanceof Projectile){
				$player->getServer()->getPluginManager()->callEvent($projectileEv = new ProjectileLaunchEvent($projectile));
				if($projectileEv->isCancelled()){
					$projectile->flagForDespawn();
				}else{
					$projectile->spawnToAll();
					//$player->getLevel()->addSound(new LaunchSound($player), $player->getViewers()); TODO: Fishing Sound
				}
			}else{
				$projectile->spawnToAll();
			}
			$pk = new EntityEventPacket();
			$pk->entityRuntimeId = $projectile->getId();
			$pk->event = EntityEventPacket::FISH_HOOK_POSITION;
			$player->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $pk);

			$session->fishingHook = $projectile;
			$session->fishing = true;
		}else{
			$projectile = $session->fishingHook;
			if($projectile instanceof FishingHook){
				$pk = new EntityEventPacket();
				$pk->entityRuntimeId = $projectile->getId();
				$pk->event = EntityEventPacket::FISH_HOOK_TEASE;
				$player->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $pk);

				$projectile->flagForDespawn();
			}

			$session->fishingHook = null;
			$session->fishing = false;
		}

		return true;
	}

	public function getProjectileEntityType(): string{
		return "FishingHook";
	}

	public function getThrowForce(): float{
		return 1.6;
	}
}