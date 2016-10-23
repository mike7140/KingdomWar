<?php
namespace KingdomWar;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\item\Item;
use pocketmine\inventory\PlayerInventory;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\event\server\RemoteServerCommandEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\plugin\PluginDescription;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\math\Vector3;
use pocketmine\math\Vector2;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\level\Explosion;
use pocketmine\level\Position;
use pocketmine\entity\Effect;
use pocketmine\nbt\NBT;
use pocketmine\inventory\Fuel;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\entity\Arrow;
use pocketmine\utils\Random;
use pocketmine\scheduler\CallbackTask;
use pocketmine\level\particle\DustParticle;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\scheduler\PluginTask;
use pocketmine\level\sound\GhastSound;
use pocketmine\level\sound\AnvilFallSound;
use pocketmine\level\particle\RedstoneParticle;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\level\particle\LargeExplodeParticle;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\block\block;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\network\Network;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\MobArmorEquipmentPacket;
use pocketmine\utils\UUID;
use pocketmine\tile\Tile;
use pocketmine\tile\Chest as TileChest;
use pocketmine\level\Level;
use pocketmine\level\sound\ClickSound;

class Main extends PluginBase implements Listener {
	public function onEnable() {
		$level = $this->getServer()->getDefaultLevel();
		$level->setAutoSave(false);
        	$plugin = "KingdomWar";
        	$this->getLogger()->info("    __  __   ____  _   __  _____ _______    ___    __  _____            ");
        	$this->getLogger()->info(" $ |  \/  |/_  __|| | / / |_____||____  / /  \    /  |/  _  |           ");
        	$this->getLogger()->info(" $ | |\/| |  | \  | |/ /  | \_______ /_/___| |___/_\_||_/_|_|_______    ");
        	$this->getLogger()->info(" $ | |  | | _|_|_ |    \  | |___    / /    | |  /__\ || \/  |           ");
        	$this->getLogger()->info(" $ |_|  |_||_____||_|\__\ |_____|  /_/    /____\   |_||____/            ");
        	$this->getLogger()->info(" $====================================================================  ");
        	$this->getLogger()->info(TextFormat::GREEN.$plugin."を読み込みました".TextFormat::BLUE." By mike7140");
        	$this->getLogger()->info(TextFormat::RED.$plugin."を二次配布するのは禁止です");
        	$this->getServer()->getPluginManager()->registerEvents($this, $this);
		if (!file_exists($this->getDataFolder())) {
            		mkdir($this->getDataFolder(), 0744, true);
	    		$this->fp = new Config($this->getDataFolder() . "fp.json", Config::JSON, array());
			$this->pd = new Config($this->getDataFolder() . "pd.json", Config::JSON, array());
			$this->pos = new Config($this->getDataFolder() . "pos.json", Config::JSON, array());
		}
    		$this->fp = new Config($this->getDataFolder() . "fp.json", Config::JSON, array());
		$this->pd = new Config($this->getDataFolder() . "pd.json", Config::JSON, array());
		$this->pos = new Config($this->getDataFolder() . "pos.json", Config::JSON, array());
		$this->team["東"] = 0;
		$this->team["南"] = 0;
		$this->team["西"] = 0;
		$this->team["北"] = 0;
		$this->count["team"] = 0;
		$this->count["message"] = 10 * 60;
		$this->count["game"] = 0;
		$this->core["HP"]["北"] = 15;
		$this->core["HP"]["北東"] = 10;
		$this->core["HP"]["東"] = 15;
		$this->core["HP"]["南東"] = 10;
		$this->core["HP"]["南"] = 15;
		$this->core["HP"]["南西"] = 10;
		$this->core["HP"]["西"] = 15;
		$this->core["HP"]["北西"] = 10;
		$this->core["HP"]["聖地"] = 50;
		$this->core["支配"]["北"] = "北";
		$this->core["支配"]["北東"] = "北";
		$this->core["支配"]["東"] = "東";
		$this->core["支配"]["南東"] = "東";
		$this->core["支配"]["南"] = "南";
		$this->core["支配"]["南西"] = "南";
		$this->core["支配"]["西"] = "西";
		$this->core["支配"]["北西"] = "西";
		$this->core["支配"]["聖地"] = "なし";
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this,"MessageTask"]), 20);
		for($aa = 1; $aa < 13; $aa++){
			if($aa == 1){
				$pos = new Vector3(126, 63, 27);
			}elseif($aa == 2){
				$pos = new Vector3(128, 63, 27);
			}elseif($aa == 3){
				$pos = new Vector3(130, 63, 27);
			}elseif($aa == 4){
				$pos = new Vector3(229, 76, 126);
			}elseif($aa == 5){
				$pos = new Vector3(229, 76, 128);
			}elseif($aa == 6){
				$pos = new Vector3(229, 76, 130);
			}elseif($aa == 7){
				$pos = new Vector3(126, 72, 229);
			}elseif($aa == 8){
				$pos = new Vector3(128, 72, 229);
			}elseif($aa == 9){
				$pos = new Vector3(130, 72, 229);
			}elseif($aa == 10){
				$pos = new Vector3(27, 68, 126);
			}elseif($aa == 11){
				$pos = new Vector3(27, 68, 128);
			}elseif($aa == 12){
				$pos = new Vector3(27, 68, 130);
			}
			$chest = $level->getTile($pos);
			if($chest instanceof TileChest){
				for($i = 0; $i < 20; $i++){
					if($i < 5){
						$item = Item::get(298, 0, 1);
					}elseif($i < 10){
						$item = Item::get(299, 0, 1);
					}elseif($i < 15){
						$item = Item::get(300, 0, 1);
					}elseif($i < 20){
						$item = Item::get(301, 0, 1);
					}
					$chest->getInventory()->getItem($i);
					$chest->getInventory()->setItem($i, $item);
					$chest->saveNBT();
				}
			}
		}
	}
/*	     北
         ○　○　○
       西○　○　○東
       　○　○　○
	     南	*/
	public function MessageTask(){
		$this->count["message"]--;
		$x = 128.5;
		$y = 70;
		$z = 128.5;
		$players = Server::getInstance()->getOnlinePlayers();
		$e = $this->getTeamColor($this->core["支配"]["東"]) . "" . $this->core["HP"]["東"];
		$se = $this->getTeamColor($this->core["支配"]["南東"]) . "" . $this->core["HP"]["南東"];
		$s = $this->getTeamColor($this->core["支配"]["南"]) . "" . $this->core["HP"]["南"];
		$sw = $this->getTeamColor($this->core["支配"]["南西"]) . "" . $this->core["HP"]["南西"];
		$w = $this->getTeamColor($this->core["支配"]["西"]) . "" . $this->core["HP"]["西"];
		$nw = $this->getTeamColor($this->core["支配"]["北西"]) . "" . $this->core["HP"]["北西"];
		$n = $this->getTeamColor($this->core["支配"]["北"]) . "" . $this->core["HP"]["北"];
		$ne = $this->getTeamColor($this->core["支配"]["北東"]) . "" . $this->core["HP"]["北東"];
		$stat = "§f東" . $e . " §f南東" . $se . " §f南" . $s . " §f南西" . $sw . " §f西" . $w . " §f北西" . $nw . " §f北" . $n . " §f北東" . $ne;
		if($this->count["message"] >= 0 and $this->count["game"] == 0){
			if($this->count["message"] < 11){
				foreach($players as $p){
					$this->setTag($p);
					$p->getLevel()->addSound(new ClickSound($p->getLocation()));
					$name = $p->getName();
					$team = $this->team[$name];
					$area = $this->getTeamLocation($p);
					$p->sendTip("§9平和時間終了まで残り§4" . $this->count["message"] . "§9秒\n§e現在地: " . $area . "\n" . $stat);
					if($team == "東"){
						if($area !== "東" and $area !== "南東" and $area !== "範囲外" and $area !== "聖地"){
							$p->sendMessage("平和時間内は他国に侵入できません");
							$this->Warp($team, $p);
						}
					}elseif($team == "南"){
						if($area !== "南" and $area !== "南西" and $area !== "範囲外" and $area !== "聖地"){
							$p->sendMessage("平和時間内は他国に侵入できません");
							$this->Warp($team, $p);
						}
					}elseif($team == "西"){
						if($area !== "西" and $area !== "北西" and $area !== "範囲外" and $area !== "聖地"){
							$p->sendMessage("平和時間内は他国に侵入できません");
							$this->Warp($team, $p);
						}
					}elseif($team == "北"){
						if($area !== "北" and $area !== "北東" and $area !== "範囲外" and $area !== "聖地"){
							$p->sendMessage("平和時間内は他国に侵入できません");
							$this->Warp($p);
						}
					}
				}
			}else{
				foreach($players as $p){
					$this->setTag($p);
					$name = $p->getName();
					$team = $this->team[$name];
					$area = $this->getTeamLocation($p);
					$p->sendTip("§9平和時間終了まで残り§4" . $this->count["message"] . "§9秒\n§e現在地: " . $area . "\n" . $stat);
					if($team == "東"){
						if($area !== "東" and $area !== "南東" and $area !== "範囲外" and $area !== "聖地"){
							$p->sendMessage("平和時間内は他国に侵入できません");
							$this->Warp($team, $p);
						}
					}elseif($team == "南"){
						if($area !== "南" and $area !== "南西" and $area !== "範囲外" and $area !== "聖地"){
							$p->sendMessage("平和時間内は他国に侵入できません");
							$this->Warp($team, $p);
						}
					}elseif($team == "西"){
						if($area !== "西" and $area !== "北西" and $area !== "範囲外" and $area !== "聖地"){
							$p->sendMessage("平和時間内は他国に侵入できません");
							$this->Warp($team, $p);
						}
					}elseif($team == "北"){
						if($area !== "北" and $area !== "北東" and $area !== "範囲外" and $area !== "聖地"){
							$p->sendMessage("平和時間内は他国に侵入できません");
							$this->Warp($p);
						}
					}
				}
			}
		}elseif($this->count["game"] == 0){

			foreach($players as $p){
				$this->setTag($p);
				$name = $p->getName();
				$team = $this->team[$name];
				$area = $this->getTeamLocation($p);
				$p->sendTip("§4現在戦争中です!!\n§e現在地: " . $area . "\n" . $stat);
			}
		}else{

			foreach($players as $p){
				$this->setTag($p);
				$name = $p->getName();
				$team = $this->team[$name];
				$area = $this->getTeamLocation($p);
				$p->sendTip("§2再起動まで" . $this->count["message"] . "秒\n§e現在地: " . $area . "\n" . $stat);
			}
		}
	}
        public function onEntityDamageByEntity(EntityDamageEvent $event){
                if ($event instanceof EntityDamageByEntityEvent) {
                    	$entity = $event->getEntity();
                    	$player = $event->getDamager();
			if($player instanceof Player and $entity instanceof Player){
				$pname = $player->getName();
				$ename = $entity->getName();
				if($this->team[$pname] == $this->team[$ename]){
					$player->sendMessage("味方同士は戦えません");
					$event->setCancelled();
				}elseif($player->x < 132 and $player->x > 124 and $player->z < 132 and $player->z >124 and $player->y > 69 and $player->y < 71){
					$event->setCancelled();
					$player->sendMessage("ロビーで戦うことは出来ません");
				}else{
					$d = $event->getDamage();
					if($this->pd->get($pname)["Job"] == "sol"){
						$d = $d + $this->pd->get($pname)["level"];
					}
					$event->setDamage($d);
				}
			}
		}
	}
	public function onPlayerLogin(PlayerLoginEvent $event) {
		$player = $event->getPlayer();
		$x = $this->getServer()->getDefaultLevel()->getSafeSpawn()->getX();
		$y = $this->getServer()->getDefaultLevel()->getSafeSpawn()->getY();
		$z = $this->getServer()->getDefaultLevel()->getSafeSpawn()->getZ();
		$level = $this->getServer()->getDefaultLevel();
		$player->setLevel($level);
		$player->teleport(new Vector3($x, $y, $z, $level));
	}
	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$player->sendMessage("§e====================");
		$player->sendMessage("§e   §3Fuwatto§e         ");
		$player->sendMessage("§e   **§4§lKingdomWar§e**   ");
		$player->sendMessage("§2http://http://mcpeffnw.game-info.wiki/");
		$player->sendMessage("§e====================");
		$player->setGamemode(0);
		$name = $player->getName();
		if(!$this->pd->exists($name)){
			$this->pd->set($name, array("Job"=>"sol", "level"=>1, "jobs"=>array("sol"=>1, "gia"=>0, "bre"=>0, "kni"=>0, "gen"=>0, "hea"=>0)));
			$this->pd->save();
		}
		if(!$this->fp->exists($name)){
			$this->fp->set($name, 500);
			$this->fp->save();
		}
		if(!isset($this->team[$name])){
			$count = $this->count["team"];
			if($count == 0){
				$player->sendMessage("あなたは東国です");
				$this->count["team"] = 1;
				$this->team[$name] = "東";
			}elseif($count == 1){
				$player->sendMessage("あなたは南国です");
				$this->count["team"] = 2;
				$this->team[$name] = "南";
			}elseif($count == 2){
				$player->sendMessage("あなたは西国です");
				$this->count["team"] = 3;
				$this->team[$name] = "西";
			}elseif($count == 3){
				$player->sendMessage("あなたは北国です");
				$this->count["team"] = 0;
				$this->team[$name] = "北";
			}
		}else{
			$team = $this->team[$name];
			$player->sendMessage("あなたは" . $team . "国です");
		}
		if($this->pd->get($name)["Job"] == "gia"){
			$level = $this->pd->get($name)["level"];
			if($level == 1){
				$lev = 1.2;
			}elseif($level == 2){
				$lev = 1.4;
			}elseif($level == 3){
				$lev = 1.6;
			}elseif($level == 4){
				$lev = 1.8;
			}elseif($level == 5){
				$lev = 2;
			}
			$player->setMaxHealth(20 * $lev);
			$player->setHealth(20 * $lev);
		}else{
			$player->setMaxHealth(20);
			$player->setHealth(20);
			if($this->pd->get($name)["Job"] == "kni"){
				$player->addEffect(Effect::getEffect(1)->setDuration(10000000)->setAmplifier(ceil($this->pd->get($name)["level"] / 2))->setVisible(true));
			}elseif($this->pd->get($name)["Job"] == "gen"){
				$player->addEffect(Effect::getEffect(5)->setDuration(10000000)->setAmplifier(ceil($this->pd->get($name)["level"] / 2))->setVisible(true));
			}
		}
		$this->setTag($player);
	}
	public function getClosePlayer($player){
		$name = $player->getName();
		$job = $this->pd->get($name)["job"];
		$pos = $player->getPosition();
		$players = Server::getInstance()->getOnlinePlayers();
		if($job == "kni"){
			$id = 1;
		}elseif($job == "gen"){
			$id = 5;
		}elseif($job == "hea"){
			$id = 10;
		}
		foreach($players as $p){
			if($name !== $p->getName()){
				if($p->distance($pos) <= 5){
					$p->addEffect(Effect::getEffect($id))->setDuration(20 * 3)->setAmplifier(ceil($this->pd->get($name)["level"] / 2)->setVisible(true));
				}
			}
		}
	}
//西128,77,228 南228,81,128 東128,68,28 北28,73,128 南西178,77,178 北西78,78,178 南東178,78,78 北東78,67,78
	public function onBreak(BlockBreakEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		$block = $event->getBlock();
		$bid = $block->getID();
		$team = $this->team[$name];
		if($bid == 159){
			$event->setCancelled();
			if($this->count["message"] > 0){
				if($this->count["game"] == 1){
					$player->sendMessage("ゲームは既に終了しています");
				}else{
					$player->sendMessage("平和時間内は削れません");
				}
			}else{
				$bx = $block->x;
				$by = $block->y;
				$bz = $block->z;
				if($bx == 128){
					if($bz == 228){
						$this->BreakCore("西", $player, $block);
					}elseif($bz == 28){
						$this->BreakCore("東", $player, $block);
					}
				}elseif($bx == 228){
					if($bz == 128){
						$this->BreakCore("南", $player, $block);
					}
				}elseif($bx == 28){
					if($bz == 128){
						$this->BreakCore("北", $player, $block);
					}
				}elseif($bx == 178){
					if($bz == 178){
						$this->BreakCore("南西", $player, $block);
					}elseif($bz == 78){
						$this->BreakCore("南東", $player, $block);
					}
				}elseif($bx == 78){
					if($bz == 178){
						$this->BreakCore("北西", $player, $block);
					}elseif($bz == 78){
						$this->BreakCore("北東", $player, $block);
					}
				}elseif($bx == 128){
					if($bz == 128){
						$this->BreakCore("聖地", $player, $block);
					}
				}
			}
		}
	}
	public function onTouch(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();
		if($block->getID() == 247){
			$name = $player->getName();
			$team = $this->team[$name];
			$l = $this->SearchWarp($team);
			if($l == "null"){
				$player->sendMessage("あなたの国は滅亡しています");
			}else{
				$this->Warp($l, $player);
			}
		}
	}
	public function SearchWarp($team){
		if($team == "北"){
			if($this->core["支配"]["北"] == "北"){
				return "北";
			}elseif($this->core["支配"]["東"] == "北"){
				return "東";
			}elseif($this->core["支配"]["西"] == "北"){
				return "北";
			}elseif($this->core["支配"]["南"] == "北"){
				return "南";
			}elseif($this->core["支配"]["北東"] == "北"){
				return "北東";
			}elseif($this->core["支配"]["北西"] == "北"){
				return "北西";
			}elseif($this->core["支配"]["南東"] == "北"){
				return "南東";
			}elseif($this->core["支配"]["南西"] == "北"){
				return "南西";
			}else{
				return "null";
			}
		}elseif($team == "東"){
			if($this->core["支配"]["東"] == "東"){
				return "東";
			}elseif($this->core["支配"]["南"] == "東"){
				return "南";
			}elseif($this->core["支配"]["北"] == "東"){
				return "北";
			}elseif($this->core["支配"]["西"] == "東"){
				return "西";
			}elseif($this->core["支配"]["南東"] == "東"){
				return "南東";
			}elseif($this->core["支配"]["北東"] == "東"){
				return "北西";
			}elseif($this->core["支配"]["南西"] == "東"){
				return "南西";
			}elseif($this->core["支配"]["北西"] == "東"){
				return "北西";
			}else{
				return "null";
			}
		}elseif($team == "南"){
			if($this->core["支配"]["南"] == "南"){
				return "南";
			}elseif($this->core["支配"]["西"] == "南"){
				return "西";
			}elseif($this->core["支配"]["東"] == "南"){
				return "東";
			}elseif($this->core["支配"]["北"] == "南"){
				return "北";
			}elseif($this->core["支配"]["南西"] == "南"){
				return "南西";
			}elseif($this->core["支配"]["南東"] == "南"){
				return "南東";
			}elseif($this->core["支配"]["北西"] == "南"){
				return "北西";
			}elseif($this->core["支配"]["北東"] == "南"){
				return "北東";
			}else{
				return "null";
			}
		}elseif($team == "西"){
			if($this->core["支配"]["西"] == "西"){
				return "西";
			}elseif($this->core["支配"]["北"] == "西"){
				return "北";
			}elseif($this->core["支配"]["南"] == "西"){
				return "南";
			}elseif($this->core["支配"]["東"] == "西"){
				return "東";
			}elseif($this->core["支配"]["北西"] == "西"){
				return "北西";
			}elseif($this->core["支配"]["南西"] == "西"){
				return "南西";
			}elseif($this->core["支配"]["北東"] == "西"){
				return "北東";
			}elseif($this->core["支配"]["南東"] == "西"){
				return "南東";
			}else{
				return "null";
			}
		}
	}
	public function getTeamLocation($player){
		$x = $player->x;
		$z = $player->z;
		if($x < 78 and $x > -22 and $z > 78 and $z < 178){
			return "北";
		}elseif($x < 78 and $x > -22 and $z > 178 and $z < 278){
			return "北西";
		}elseif($x < 78 and $x > -22 and $z > -22 and $z < 78){
			return "北東";
		}elseif($x < 178 and $x > 78 and $z > 78 and $z < 178){
			return "聖地";
		}elseif($x < 178 and $x > 78 and $z > -22 and $z < 78){
			return "東";
		}elseif($x < 178 and $x > 78 and $z > 178 and $x < 278){
			return "西";
		}elseif($x < 278 and $x > 178 and $z > -22 and $z < 78){
			return "南東";
		}elseif($x < 278 and $x > 178 and $z > 78 and $z < 178){
			return "南";
		}elseif($x < 278 and $x > 178 and $z > 178 and $z < 278){
			return "南西";
		}else{
			return "範囲外";
		}
	}
	public function Warp($l, $player){
		if($l == "リスポ"){
			$pos = new Vector3(128.5, 69, 128.5);
		}elseif($l == "西"){
			$pos = new Vector3(128.5, 72, 222.5);
		}elseif($l == "南西"){
			$pos = new Vector3(175, 73, 175);
		}elseif($l == "南"){
			$pos = new Vector3(222.5, 76, 128.5);
		}elseif($l == "南東"){
			$pos = new Vector3(175.5, 74, 81.5);
		}elseif($l == "東"){
			$pos = new Vector3(128.5, 63, 34.5);
		}elseif($l == "北東"){
			$pos = new Vector3(84, 64, 82);
		}elseif($l == "北"){
			$pos = new Vector3(34.5, 68, 128.5);
		}
		$player->teleport($pos);
		return true;
	}
	public function BreakCore($l, $player, $block){
		$name = $player->getName();
		if($l == $this->core["支配"][$l]){
			$player->sendMessage("自分の国のWarBlockは破壊できません");
			return false;
		}
		if($this->pd->get($name)["Job"] == "bre"){
			$lev = $this->pd->get($name)["level"];
		}else{
			$lev = 1;
		}
		$this->core["HP"][$l] = $this->core["HP"][$l] - $lev;
		$this->getServer()->broadcastMessage($l . "のWarBlockが" . $name . "により" . $lev . "削られました(残り" . $this->core["HP"][$l] . ")");
		$this->Warp($this->SearchWarp($this->team[$name]), $player);
		$player->getInventory()->addItem(Item::get(371, 0, 10));
		if($this->core["HP"][$l] <= 0){
			$team = $this->team[$name];
			$this->getServer()->broadcastMessage($l . "のWarBlockが破壊され" . $team . "国が占領しました");
			$this->core["支配"][$l] = $team;
			if($team == "西"){
				$player->getLevel()->setBlock(new Vector3($block->x, $block->y, $block->z), Block::get(159, 4));
			}elseif($team == "北"){
				$player->getLevel()->setBlock(new Vector3($block->x, $block->y, $block->z), Block::get(159, 3));
			}elseif($team == "東"){
				$player->getLevel()->setBlock(new Vector3($block->x, $block->y, $block->z), Block::get(159, 5));
			}elseif($team == "南"){
				$player->getLevel()->setBlock(new Vector3($block->x, $block->y, $block->z), Block::get(159, 14));
			}
			if($l == "北" or $l == "南" or $l == "西" or $l == "東"){
				$this->core["HP"][$l] = 30;
			}elseif($l == "南東" or $l == "南西" or $l == "北東" or $l == "北西"){
				$this->core["HP"][$l] = 20;
			}elseif($l == "聖地"){
				$this->core["HP"][$l] = 50;
			}
			$players = Server::getInstance()->getOnlinePlayers();
			foreach($players as $p){
				$n = $p->getName();
				if($team !== $this->team[$n]){
					if($this->getTeamLocation($player) == $l){
						$player->kill();
						$player->sendMessage("あなたの場所は敵陣地に占領されました");
					}
				}
			}
			if(!in_array("北", $this->core["支配"])){
				if($this->team["北"] == 0){
					$this->getServer()->broadcastMessage("北国は領土を失いました");
					$this->team["北"] = 1;
					$players = Server::getInstance()->getOnlinePlayers();
					foreach($players as $p){
						if($this->team[$p->getName()] == "北"){
							$this->Warp("リスポ", $p);
							$p->sendMessage("北国は敗戦しました。/change <n|w|s|e>で国を変更してください。");
						}
					}
				}
			}elseif(!in_array("西", $this->core["支配"])){
				if($this->team["西"] == 0){
					$this->getServer()->broadcastMessage("西国は領土を失いました");
					$this->team["西"] = 1;
					$players = Server::getInstance()->getOnlinePlayers();
					foreach($players as $p){
						if($this->team[$p->getName()] == "西"){
							$this->Warp("リスポ", $p);
							$p->sendMessage("西国は敗戦しました。/change <n|w|s|e>で国を変更してください。");
						}
					}
				}
			}elseif(!in_array("東", $this->core["支配"])){
				if($this->team["東"] == 0){
					$this->getServer()->broadcastMessage("東国は領土を失いました");
					$this->team["東"] = 1;
					$players = Server::getInstance()->getOnlinePlayers();
					foreach($players as $p){
						if($this->team[$p->getName()] == "東"){
							$this->Warp("リスポ", $p);
							$p->sendMessage("東国は敗戦しました。/change <n|w|s|e>で国を変更してください。");
						}
					}
				}
			}elseif(!in_array("南", $this->core["支配"])){
				if($this->team["南"] == 0){
					$this->getServer()->broadcastMessage("南国は領土を失いました");
					$this->team["南"] = 1;
					$players = Server::getInstance()->getOnlinePlayers();
					foreach($players as $p){
						if($this->team[$p->getName()] == "南"){
							$this->Warp("リスポ", $p);
							$p->sendMessage("南国は敗戦しました。/change <n|w|s|e>で国を変更してください。");
						}
					}
				}
			}
			if($this->team["東"] == 0 and $this->team["西"] == 1 and $this->team["北"] == 1 and $this->team["南"] == 1){
				$this->count["game"] = 1;
				$this->getServer()->broadcastmessage("全ての領土が東国になりゲームが終了しました！");
				$players = Server::getInstance()->getOnlinePlayers();
				foreach($players as $p){
					$this->warp("リスポ", $p);
					$n = $player->getName();
					if($this->team[$n] == "東"){
						$this->getRandomJob($p);
					}
				}
			}elseif($this->team["東"] == 1 and $this->team["西"] == 0 and $this->team["北"] == 1 and $this->team["南"] == 1){
				$this->count["game"] = 1;
				$this->getServer()->broadcastmessage("全ての領土が西国になりゲームが終了しました！");
				$players = Server::getInstance()->getOnlinePlayers();
				foreach($players as $p){
					$this->warp("リスポ", $p);
					$n = $player->getName();
					if($this->team[$n] == "西"){
						$this->getRandomJob($p);
					}
				}
			}elseif($this->team["東"] == 1 and $this->team["西"] == 1 and $this->team["北"] == 0 and $this->team["南"] == 1){
				$this->count["game"] = 1;
				$this->getServer()->broadcastmessage("全ての領土が北国になりゲームが終了しました！");
				$players = Server::getInstance()->getOnlinePlayers();
				foreach($players as $p){
					$this->warp("リスポ", $p);
					$n = $player->getName();
					if($this->team[$n] == "北"){
						$this->getRandomJob($p);
					}
				}
			}elseif($this->team["東"] == 1 and $this->team["西"] == 1 and $this->team["北"] == 1 and $this->team["南"] == 0){
				$this->count["game"] = 1;
				$this->getServer()->broadcastmessage("全ての領土が南国になりゲームが終了しました！");
				$players = Server::getInstance()->getOnlinePlayers();
				foreach($players as $p){
					$this->warp("リスポ", $p);
					$n = $player->getName();
					if($this->team[$n] == "南"){
						$this->getRandomJob($p);
					}
				}
			}
		}
		return true;
	}
	public function getRandomJob($player){
		$name = $player->getName();
		$r = mt_rand(0, 99);
		if($r == 0){
			if($this->pd->get($name)["jobs"]["gia"] == 0){
				$player->sendMessage("Giantのjobがアンロックされました！");
				$this->pd->set($name, array_merge($this->pd->get($name), array("job"=>array("gia"=>1))));
				$this->pd->save();
			}elseif($this->pd->get($name)["level"] !== 5){
				$player->sendMessage("JobLevelが上がりました！");
				$this->pd->set($name, array_merge($this->pd->get($name), array("level"=>$this->pd->get($name)["level"] + 1)));
				$this->pd->save();
			}
		}elseif($r == 1){
			if($this->pd->get($name)["jobs"]["bre"] == 0){
				$player->sendMessage("Breakerのjobがアンロックされました！");
				$this->pd->set($name, array_merge($this->pd->get($name), array("job"=>array("breaker"=>1))));
				$this->pd->save();
			}elseif($this->pd->get($name)["level"] !== 5){
				$player->sendMessage("JobLevelが上がりました！");
				$this->pd->set($name, array_merge($this->pd->get($name), array("level"=>$this->pd->get($name)["level"] + 1)));
				$this->pd->save();
			}
		}elseif($r == 2){
			if($this->pd->get($name)["jobs"]["Knight"] == 0){
				$player->sendMessage("Knightのjobがアンロックされました！");
				$this->pd->set($name, array_merge($this->pd->get($name), array("job"=>array("kni"=>1))));
				$this->pd->save();
			}elseif($this->pd->get($name)["level"] !== 5){
				$player->sendMessage("JobLevelが上がりました！");
				$this->pd->set($name, array_merge($this->pd->get($name), array("level"=>$this->pd->get($name)["level"] + 1)));
				$this->pd->save();
			}
		}elseif($r == 3){
			if($this->pd->get($name)["jobs"]["General"] == 0){
				$player->sendMessage("Generalのjobがアンロックされました！");
				$this->pd->set($name, array_merge($this->pd->get($name), array("job"=>array("gen"=>1))));
				$this->pd->save();
			}elseif($this->pd->get($name)["level"] !== 5){
				$player->sendMessage("JobLevelが上がりました！");
				$this->pd->set($name, array_merge($this->pd->get($name), array("level"=>$this->pd->get($name)["level"] + 1)));
				$this->pd->save();
			}
		}elseif($r == 4){
			if($this->pd->get($name)["jobs"]["hea"] == 0){
				$player->sendMessage("Healerのjobがアンロックされました！");
				$this->pd->set($name, array_merge($this->pd->get($name), array("job"=>array("hea"=>1))));
				$this->pd->save();
			}elseif($this->pd->get($name)["level"] !== 5){
				$player->sendMessage("JobLevelが上がりました！");
				$this->pd->set($name, array_merge($this->pd->get($name), array("level"=>$this->pd->get($name)["level"] + 1)));
				$this->pd->save();
			}
		}
		return true;
	}
	public function getJobName($j){
		if($j == "sol"){
			return "Soldier";
		}elseif($j == "gia"){
			return "Giant";
		}elseif($j == "bre"){
			return "Breaker";
		}elseif($j == "kni"){
			return "Knight";
		}elseif($j == "gen"){
			return "General";
		}elseif($j == "hea"){
			return "Healer";
		}else{
			return "null";
		}
	}
	public function getTeamColor($team){
		if($team == "東"){
			return "§2";
		}elseif($team == "北"){
			return "§3";
		}elseif($team == "西"){
			return "§6";
		}elseif($team == "南"){
			return "§c";
		}else{
			return "§f";
		}
	}
	public function setTag($player){
		$name = $player->getName();
		$job = $this->getJobName($this->pd->get($name)["Job"]);
		$hp = str_repeat("♥", ceil($player->getHealth() / 4));
		$team = $this->team[$name];
		$teamc = $this->getTeamColor($team);
		$player->setNameTag($teamc . "" . $team . "§f[" . $job . "]" . $name . "\n§5" . $hp . "§f");
		$player->setDisplayName($teamc . "" . $team . "§f[" . $job . "]" . $name . "§5" . $hp . "§f");
		return true;
	}
	public function onDeath(PlayerDeathEvent $event){
		$player = $event->getPlayer();
		$event1 = $player->getLastDamageCause();
		if ($event1 instanceof EntityDamageByEntityEvent) {
			$killer = $event1->getDamager();
			$killer->getInventory()->addItem(Item::get(371, 0, 5));
		}
	}
    	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		$name = $sender->getName();
		if($command->getName() == "jobs"){
			if(!isset($args[0])){
				$sender->sendMessage("jobsコマンド");
				$sender->sendMessage("/jobs list        |jobリストを表示します");
				$sender->sendMessage("/jobs join <job名>|jobに就きます");
			}elseif($args[0] == "list"){
				$sender->sendMessage("========jobリスト========");
				$sender->sendMessage("Soldier[sol]|Lv." . $this->pd->get($name)["jobs"]["sol"] . "|攻撃力が上昇します");
				if($this->pd->get($name)["jobs"]["gia"] !== 0){
					$sender->sendMessage("Giant  [gia]|Lv." . $this->pd->get($name)["jobs"]["gia"] . "|初期HPが上昇します");
				}else{
					$sender->sendMessage("*****  [***]|****|ロック中...");
				}
				if($this->pd->get($name)["jobs"]["bre"] !== 0){
					$sender->sendMessage("Breaker[bre]|Lv." . $this->pd->get($name)["jobs"]["bre"] . "|コアHPをより多く削ります");
				}else{
					$sender->sendMessage("*****  [***]|****|ロック中...");
				}
				if($this->pd->get($name)["jobs"]["kni"] !== 0){
					$sender->sendMessage("Knight [kni]|Lv." . $this->pd->get($name)["jobs"]["kni"] . "|自分と周囲の味方の移動速度が上昇します");
				}else{
					$sender->sendMessage("*****  [***]|****|ロック中...");
				}
				if($this->pd->get($name)["jobs"]["gen"] !== 0){
					$sender->sendMessage("General[gen]|Lv." . $this->pd->get($name)["jobs"]["gen"] . "|自分と周囲の味方の攻撃力が上昇します");
				}else{
					$sender->sendMessage("*****  [***]|****|ロック中...");
				}
				if($this->pd->get($name)["jobs"]["hea"] !== 0){
					$sender->sendMessage("Healer [hea]|Lv." . $this->pd->get($name)["jobs"]["hea"] . "|周囲の味方が回復します");
				}else{
					$sender->sendMessage("*****  [***]|****|ロック中...");
				}
			}elseif($args[0] == "join"){
				if(!isset($args[1])){
					$sender->sendMessage("/jobs join <job名>");
				}else{
					switch($args[1]){
						case "sol":
							$sender->kick("JobをSoldierに変更しました", false);
							$this->pd->set($name, array_merge($this->pd->get($name), array("Job"=>"sol", "level"=>1)));
							$this->pd->save();
							break;
						case "gia":
						case "bre":
						case "kni":
						case "gen":
						case "hea":
							if($this->pd->get($name)["jobs"]["gia"] == 1){
								$jobname = $this->getJobName($args[1]);
								$sender->kick("Jobを" . $jobname . "に変更しました", false);
								$this->pd->set($name, array_merge($this->pd->get($name), array("Job"=>$args[1], "level"=>1)));
								$this->pd->save();
								$this->setTag($sender);
							}else{
								$sender->sendMessage("まだこのjobはアンロックされていません");
							}
							break;
						default:
							$sender->sendMessage("そのJobは存在しません");
							break;
					}
				}
			}else{
				$sender->sendMessage("jobsコマンド");
				$sender->sendMessage("/jobs list        |jobリストを表示します");
				$sender->sendMessage("/jobs join <job名>|jobに就きます");
			}
		}elseif($command->getName() == "change"){
			if(!isset($args[0])){
				$sender->sendMessage("/change <e|s|w|n>");
			}elseif($args[0] == "e"){
				if($this->team["東"] == 1){
					$sender->sendMessage("東はすでに滅亡しています");
				}else{
					$sender->sendMessage("東国に変更しました");
					$this->team[$name] == "東";
					$this->setTag($player);
				}
			}elseif($args[0] == "s"){
				if($this->team["南"] == 1){
					$sender->sendMessage("南はすでに滅亡しています");
				}else{
					$sender->sendMessage("南国に変更しました");
					$this->team[$name] == "南";
					$this->setTag($player);
				}
			}elseif($args[0] == "w"){
				if($this->team["西"] == 1){
					$sender->sendMessage("西はすでに滅亡しています");
				}else{
					$sender->sendMessage("西国に変更しました");
					$this->team[$name] == "西";
					$this->setTag($player);
				}
			}elseif($args[0] == "n"){
				if($this->team["北"] == 1){
					$sender->sendMessage("北はすでに滅亡しています");
				}else{
					$sender->sendMessage("北国に変更しました");
					$this->team[$name] == "北";
					$this->setTag($player);
				}
			}else{
				$sender->sendMessage("/change <e|s|w|n>");
			}
		}elseif($command->getName() == "shop"){
			if(!isset($args[0])){
				$sender->sendMessage("===***======SHOP======***===");//10FP=1金塊
				$sender->sendMessage("商品      | 必要なもの    | コマンド");
				$sender->sendMessage("金の購入");
				$sender->sendMessage("金塊10個  | 150FP         | /shop gn10");
				$sender->sendMessage("金塊50個  | 550FP         | /shop gn50");
				$sender->sendMessage("FPに換金");
				$sender->sendMessage("50FP      | 金1個         | /shop 50FP");
				$sender->sendMessage("480FP     | 金ブロック1個 | /shop 480FP");
				$sender->sendMessage("資源/道具の購入");
				$sender->sendMessage("原木1stack| 金3個         | /shop wood");
				$sender->sendMessage("丸石1stack| 金3個         | /shop stone");
				$sender->sendMessage("小麦1stack| 金3個         | /shop seed");
			}else{
				switch($args[0]){
					case "gn10":
						if($this->fp->get($name) < 150){
							$sender->sendMessage("FPが不足しています");
						}else{
							$sender->getInventory()->addItem(Item::get(371, 0, 10));
							$this->fp->set($name, $this->fp->get($name) - 150);
							$this->fp->save();
							$sender->sendMessage("金塊10個を購入しました");
						}
					break;
					case "gn50":
						if($this->fp->get($name) < 550){
							$sender->sendMessage("FPが不足しています");
						}else{
							$sender->getInventory()->addItem(Item::get(371, 0, 50));
							$this->fp->set($name, $this->fp->get($name) - 550);
							$this->fp->save();
							$sender->sendMessage("金塊50個を購入しました");
						}
					break;
					case "50FP":
						if(!$sender->getInventory()->contains(Item::get(266, 0, 1))){
							$sender->sendMessage("金が足りません");
						}else{
							$sender->getInventory()->removeItem(Item::get(266, 0, 1));
							$this->fp->set($name, $this->fp->get($name) + 50);
							$this->fp->save();
							$sender->sendMessage("金1個を50FPに交換しました");
						}
					break;
					case "480FP":
						if(!$sender->getInventory()->contains(Item::get(41, 0, 1))){
							$sender->sendMessage("金ブロックが足りません");
						}else{
							$sender->getInventory()->removeItem(Item::get(41, 0, 1));
							$this->fp->set($name, $this->fp->get($name) + 480);
							$this->fp->save();
							$sender->sendMessage("金ブロック1個を480FPに交換しました");
						}
					break;
					case "wood":
						if(!$sender->getInventory()->countains(Item::get(266, 0, 3))){
							$sender->sendMessage("金が足りません");
						}else{
							$sender->getInventory()->removeItem(Item::get(266, 0, 3));
							$sender->getInventory()->addItem(Item::get(17, 0, 64));
							$sender->sendMessage("金3個を原木64個に交換しました");
						}
					break;
					case "stone":
						if(!$sender->getInventory()->countains(Item::get(266, 0, 3))){
							$sender->sendMessage("金が足りません");
						}else{
							$sender->getInventory()->removeItem(Item::get(266, 0, 3));
							$sender->getInventory()->addItem(Item::get(4, 0, 64));
							$sender->sendMessage("金3個丸石64個に交換しました");
						}
					break;
					case "seed":
						if(!$sender->getInventory()->countains(Item::get(266, 0, 3))){
							$sender->sendMessage("金が足りません");
						}else{
							$sender->getInventory()->removeItem(Item::get(266, 0, 3));
							$sender->getInventory()->addItem(Item::get(295, 0, 64));
							$sender->sendMessage("金3個を種64個に交換しました");
						}
					break;
					default:
						$sender->sendMessage("その商品は存在しません");
					break;
				}
			}
		}
	}
}

















































