<?php
namespace Auth;

use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;

class Auth extends PluginBase implements Listener{

	public $login = [];
	
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->exec();

	}

    public function exec(){
        $this->getLogger()->info(TextFormat::YELLOW."(Multi-Serv) Working...");

        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder()."Players");
        
        $this->saveDefaultConfig();
        $this->getResource("config.yml");

        $this->HOST = $this->getConfig()->get("HOST");

        if($this->getConfig()->get("mysqli.work.?") != false){
            
            $this->getLogger()->info(TextFormat::GREEN."Mysql is activated.");

            $this->USER = $this->getConfig()->get("USER");
            $this->PASS = $this->getConfig()->get("PASS");

            $this->TABLE = "Auth"."(PLAYER VARCHAR(255), IP VARCHAR(30), PASSWORD VARCHAR(65536))";

            $mysqli = new \mysqli($this->HOST, $this->USER, $this->PASS);
                $mysqli->query("CREATE DATABASE IF NOT EXISTS "."Auth");

            $mysqli = new \mysqli($this->HOST, $this->USER, $this->PASS, "Auth");
                $mysqli->query("CREATE TABLE IF NOT EXISTS ".$this->TABLE);

        }else{
            $this->getLogger()->info(TextFormat::GOLD."Mysql is disabled, check in config.yml if you want activate it.");

        }

    }

	public function Join(PlayerJoinEvent $event){
		$player = $event->getPlayer();

		$this->login[$player->getName()] = false;

	 	if($this->isRegister($player) == true){ //Player is register ?

			if($this->isLoginIp($player) == true){ // Player is login ip ?
				$this->isLogin($player, true);

				$this->login[$player->getName()] = true;

				$player->sendMessage($this->getConfig()->get("login.success.ip.message"));

			}else{
				$this->isLogin($player, false);
				$player->sendMessage($this->getConfig()->get("login.join.message"));
 
            }

	 	}else{
            $this->isLogin($player, false);
            $player->sendMessage($this->getConfig()->get("register.join.message"));

		}

	}

	public function onCommand(CommandSender $sender, Command $command, $label, array $args){

		switch($command->getName()){

            case "register": //all
                if($this->isRegister($sender) == false && $this->login[$sender->getName()] == false){

                	if(!empty($args[0])){
                		$this->Register($sender, $args[0]);
                		$this->isLogin($sender, true);

                        $this->login[$sender->getName()] = true;

                        $sender->sendMessage($this->getConfig()->get("register.success.message"));

                	}else{
                		$sender->sendMessage($this->getConfig()->get("register.error.message"));

                	}

                }

            break;

            case "login": //all
                if($this->isRegister($sender) == true && $this->login[$sender->getName()] == false){
                    
                	if($this->Login($sender, $args[0]) == true){
                        $this->isLogin($sender, true);
                        $this->changeIp($sender);

                        $this->login[$sender->getName()] = true;

                        $sender->sendMessage($this->getConfig()->get("login.success.message"));

                	}else{
                		$sender->sendMessage($this->getConfig()->get("login.error.message"));

                	}

                }

            break;

            case "unregister": //op
                if($sender->isOp()){

                	if($this->unRegister($args[0]) == true){
                        $sender->sendMessage($this->getConfig()->get("unregister.success.message"));

                    }else{
                    	$sender->sendMessage($this->getConfig()->get("unregister.error.message"));

                    }

                }

            break;

        }

    }


	public function isRegister(Player $player){
        $name = $player->getName();

        if($this->getConfig()->get("mysqli.work.?") == false){

		    if(file_exists($this->getDataFolder()."Players"."/".$name.".yml")){
                return true;
			
            }else{
			    return false;

            }

        }else{
            $mysqli = new \mysqli($this->HOST, $this->USER, $this->PASS, "Auth");

            if($mysqli->query("SELECT * FROM "."Auth"." WHERE PLAYER = '$name'")->num_rows > 0){
                return true;
            
            }else{
                return false;

            }

        }

	}

	public function isLoginIp(Player $player){
        $address = $player->getAddress();
        $name = $player->getName();

        if($this->getConfig()->get("mysqli.work.?") == false){

            $file = new Config($this->getDataFolder()."Players"."/".$name.".yml", Config::YAML);

            if($address == $file->get("IP")){
			    return true;
			
		    }else{
			    return false;

            }

        }else{
            $mysqli = new \mysqli($this->HOST, $this->USER, $this->PASS, "Auth");
            $info = mysqli_fetch_row($mysqli->query("SELECT * FROM "."Auth"." WHERE PLAYER = '$name'"));

            if($address == $info[1]){
                return true;
            
            }else{
                return false;

            }

        }



	}

	public function isLogin(Player $player, $valid){
        $player->setImmobile(false);
		$player->setGamemode(0);

		if($valid == false){
			$player->setImmobile(true);
			$player->setGamemode(3);
			
		}

    }

    public function changeIp(Player $player){
        $address = $player->getAddress();
        $name = $player->getName();

        if($this->getConfig()->get("mysqli.work.?") == false){

            $file = new Config($this->getDataFolder()."Players"."/".$player->getName().".yml", Config::YAML);

            $file->set("IP", $address);
            $file->save();

        }else{
            $mysqli = new \mysqli($this->HOST, $this->USER, $this->PASS, "Auth");

            $mysqli->query("UPDATE "."Auth"." SET IP = '$address' WHERE PLAYER = '$name'");

        }

    }

    public function Register(Player $player, $password){
        $address = $player->getAddress();
        $name = $player->getName();
        $passmd5 = md5($password);

        if($this->getConfig()->get("mysqli.work.?") == false){

            $file = new Config($this->getDataFolder()."Players"."/".$player->getName().".yml", Config::YAML);

            $file->set("IP", $player->getAddress());
            $file->set("PASSWORD", $passmd5);
            $file->save();

        }else{
            $mysqli = new \mysqli($this->HOST, $this->USER, $this->PASS, "Auth");

            $mysqli->query("INSERT INTO "."Auth"."(PLAYER, IP, PASSWORD) VALUES ('$name', '$address', '$passmd5')");

        }

    }

    public function Login(Player $player, $password){
        $name = $player->getName();
        $passmd5 = md5($password);

        if($this->getConfig()->get("mysqli.work.?") == false){

		    $file = new Config($this->getDataFolder()."Players"."/".$name.".yml", Config::YAML);

		    if($passmd5 == $file->get("PASSWORD")){
                return true;
			
		    }else{
			    return false;

            }

        }else{
            $mysqli = new \mysqli($this->HOST, $this->USER, $this->PASS, "Auth");
            $info = mysqli_fetch_row($mysqli->query("SELECT PLAYER, IP, PASSWORD FROM "."Auth"." WHERE PLAYER = '$name'"));

            if($passmd5 == $info[2]){
                return true;
            
            }else{
                return false;

            }

        }

	}

    public function unRegister($name){
        $i = $this->getServer()->getPlayer($name);

        if($i){
            $i->kick($this->getConfig()->get("kick.player.message"));

        }

        if($this->getConfig()->get("mysqli.work.?") == false){

            if(file_exists($this->getDataFolder()."Players"."/".$name.".yml")){
                unlink($this->getDataFolder()."Players"."/".$name.".yml");

                return true;

            }else{
                return false;

            }
   
        }else{
            $mysqli = new \mysqli($this->HOST, $this->USER, $this->PASS, "Auth");

            if($mysqli->query("SELECT * FROM "."Auth"." WHERE PLAYER = '$name'")->num_rows > 0){
                $mysqli->query("DELETE FROM "."Auth"." WHERE PLAYER = '$name'");

                return true;
            
            }else{
                return false;

            }

        }

    }

    
}





