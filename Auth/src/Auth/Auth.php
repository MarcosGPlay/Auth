<?php
namespace Auth;

use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;

class Auth extends PluginBase implements Listener{

	public $login = [];
	
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->configuring();

	}

	public function configuring(){
		$this->config = new Config($this->getDataFolder()."config.yml", Config::YAML, 
        [

        "register.join.message" => "§eWelcome, Use /register <password> to logged in.",
        "register.success.message" => "§aYou have been logged in.",
        "register.error.message" => "§cUse /register <password>.",

        "login.join.message" => "§eWelcome, Use /login <password> to logged in.",
        "login.success.message" => "§aYou have been logged in.",
        "login.success.ip.message" => "§aYou have been logged with your Ip.",
        "login.error.message" => "§cWrong password.",

        "unregister.success.message" => "§aYou have unregister the player",
        "unregister.error.message" => "§cPlayer does not exist.",

        "kick.player.message" => "§cYou account has been unregister by an admin."

        ]);

        $this->config->save();

        @mkdir($this->getDataFolder()."Players");

	}

	public function Join(PlayerJoinEvent $event){
		$player = $event->getPlayer();

		$this->login[$player->getName()] = false;

	 	if($this->isRegister($player) == true){ //Player is register ?

			if($this->isLoginIp($player) == true){ // Player is login ip ?
				$this->isLogin($player, true);

				$this->login[$player->getName()] = true;

				$player->sendMessage($this->config->get("login.success.ip.message"));

			}else{
				$this->isLogin($player, false);
				$player->sendMessage($this->config->get("login.join.message"));
 
            }

	 	}else{
            $this->isLogin($player, false);
            $player->sendMessage($this->config->get("register.join.message"));

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

                        $sender->sendMessage($this->config->get("register.success.message"));

                	}else{
                		$sender->sendMessage($this->config->get("register.error.message"));

                	}

                }

            break;

            case "login": //all
                if($this->isRegister($sender) == true && $this->login[$sender->getName()] == false){
                    
                	if($this->Login($sender, $args[0]) == true){
                        $this->isLogin($sender, true);
                        $this->changeIp($sender);

                        $this->login[$sender->getName()] = true;

                        $sender->sendMessage($this->config->get("login.success.message"));

                	}else{
                		$sender->sendMessage($this->config->get("login.error.message"));

                	}

                }

            break;

            case "unregister": //op
                if($sender->isOp()){

                	if(file_exists($this->getDataFolder()."Players"."/".$args[0].".yml")){
                        unlink($this->getDataFolder()."Players"."/".$args[0].".yml");

                        $sender->sendMessage($this->config->get("unregister.success.message"));

                        $i = $this->getServer()->getPlayer($args[0]);

                        if($i){
                        	$i->kick($this->config->get("kick.player.message"));

                        }

                    }else{
                    	$sender->sendMessage($this->config->get("unregister.error.message"));

                    }

                }

            break;

        }

    }


	public function isRegister(Player $player){

		if(file_exists($this->getDataFolder()."Players"."/".$player->getName().".yml")){
			return true;
			
		}else{
			return false;

        }

	}

	public function isLoginIp(Player $player){
		$file = new Config($this->getDataFolder()."Players"."/".$player->getName().".yml", Config::YAML);

		if($player->getAddress() == $file->get("IP")){
			return true;
			
		}else{
			return false;

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
        $file = new Config($this->getDataFolder()."Players"."/".$player->getName().".yml", Config::YAML);

        $file->set("IP", $player->getAddress());

        $file->save();

    }

    public function Register(Player $player, $password){
    	$file = new Config($this->getDataFolder()."Players"."/".$player->getName().".yml", Config::YAML);

    	$file->set("IP", $player->getAddress());
		$file->set("PASSWORD", md5($password));

		$file->save();

    }

    public function Login(Player $player, $password){
		$file = new Config($this->getDataFolder()."Players"."/".$player->getName().".yml", Config::YAML);

		if(md5($password) == $file->get("PASSWORD")){
			return true;
			
		}else{
			return false;

        }

	}


}
