<?php

/*
       _             _____           ______ __
      | |           |  __ \         |____  / /
      | |_   _ _ __ | |  | | _____   __ / / /_
  _   | | | | | '_ \| |  | |/ _ \ \ / // / '_ \
 | |__| | |_| | | | | |__| |  __/\ V // /| (_) |
  \____/ \__,_|_| |_|_____/ \___| \_//_/  \___/


This program was produced by JunDev76 and cannot be reproduced, distributed or used without permission.

Developers:
 - JunDev76 (https://github.jundev.me/)

Copyright 2022. JunDev76. Allrights reserved.
*/

namespace JunDev76\SteakMapImageSystem;

use JsonException;
use JunKR\CrossUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\permission\DefaultPermissions;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

class SteakMapImageSystem extends PluginBase{
    use SingletonTrait;

    public function onLoad() : void{
        self::setInstance($this);
    }

    public array $db = [];

    /**
     * @throws JsonException
     */
    public function onEnable() : void{
        CrossUtils::registercommand('steakmapimagesystem', $this, '', DefaultPermissions::ROOT_OPERATOR);
        $this->db = CrossUtils::getDataArray($this->getDataFolder() . 'data.json');
        $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() : void{
            $this->loadImage(($this->db['won'] ?? '정보 없음'));
        }), 20);
    }

    /**
     * @throws JsonException
     */
    public function onDisable() : void{
        file_put_contents($this->getDataFolder() . 'data.json', json_encode($this->db, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
        if($command->getName() === 'steakmapimagesystem'){
            if(($argv = ($args[0] ?? null)) === null){
                return true;
            }
            $this->loadImage($argv);
        }
        return true;
    }

    public function loadImage(string $steak_won) : void{
        $this->db['won'] = $steak_won;
        $this->getServer()->getAsyncPool()->submitTask(new class($steak_won) extends AsyncTask{

            public string $data;

            public function __construct(public string $steak_won){
            }

            public function onRun() : void{
                $ch = curl_init(); // 리소스 초기화

                $url = 'https://www.crsbe.kr/steak_image/?text=' . $this->steak_won;

                // 옵션 설정
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

                $this->data = curl_exec($ch);

                curl_close($ch);  // 리소스 해제
            }

            public function onCompletion() : void{
                $temp = tmpfile();
                fwrite($temp, $this->data);
                fseek($temp, 0);
                $path = stream_get_meta_data($temp)['uri'];
                rename($path, $path . '.png');
                $path .='.png';
                (new SteakImagePlaceSession($path, new Vector3(220, 68, 210), new Vector3(224, 66, 210), Server::getInstance()->getWorldManager()->getWorldByName('spawnworld')));
                fclose($temp);
            }
        });
    }
}
