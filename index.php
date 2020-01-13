<?php

//ログ出力
ini_set('log_errors','on');
ini_set('error_log','php.log');
session_start();

//鬼達格納用
$monsters = array();
//鳴き声クラス
class Cry{
    const SMALL = 1;
    const MEDIUM = 2;
    const BIG = 3;
}


//抽象クラス(生き物クラス)
abstract class Creature{
    protected $name;
    protected $hp;
    protected $maxHp;
    protected $attackMin;
    protected $attackMax;
    abstract public function sayCry();
    public function setName($str){
        $this->name = $str;
    }
    public function getName(){
        return $this->name;
    }
    public function setHp($num){
        $this->hp = $num;
    }
    public function getHp(){
        return $this->hp;
    }
    public function setMaxHp($num){
        $this->maxHp = $num;
    }
    public function getMaxHp(){
        return $this->maxHp;
    }
    public function attack($targetObj){
        $attackPoint = mt_rand($this->attackMin, $this->attackMax);
        if(!mt_rand(0,9)){//10分の1の確率でクリティカル
            $attackPoint = $attackPoint * 1.5;
            $attackPoint = (int)$attackPoint;
            History::set($this->getName().'のクリティカルヒット！！');
        }
        $targetObj->setHp($targetObj->getHp()-$attackPoint);
        History::set($attackPoint.'ポイントのダメージ！');
    }
}
//人クラス
class Human extends Creature{
    public function __construct($name,$hp,$attackMin,$attackMax){
        $this->name = $name;
        $this->hp = $hp;
        $this->attackMin = $attackMin;
        $this->attackMax = $attackMax;
    }
    public function sayCry(){
        return('なーーーん');
    }
}

//鬼クラス
class Monster extends Creature{
    //プロパティ
    protected $img;
    protected $cry;
    //コンストラクタ
    public function __construct($name,$hp,$maxHp,$img,$attackMin,$attackMax,$cry){
        $this->name = $name;
        $this->hp = $hp;
        $this->maxHp = $maxHp;
        $this->img = $img;
        $this->attackMin = $attackMin;
        $this->attackMax = $attackMax;
        $this->cry = $cry;
    }
    public function getImg(){
        return $this->img;
    }
    public function setCry($num){
        $this->cry = $num;
    }
    public function getCry(){
        return $this->cry;
    }
    public function sayCry(){
        switch($this->cry){
            case Cry::SMALL :
                return('悪い子はいねーかー！');
                break;
            case Cry::MEDIUM :
                return('豆をよこせーー！');
                break;
            case Cry::BIG :
                return('泣く子はいねーかー！');
                break;
        }
    }
    
    
}

//強い鬼クラス
class PowerMonster extends Monster{
    private $powerAttack;
    function __construct($name,$hp,$maxHp,$img,$attackMin,$attackMax,$cry,$powerAttack){
        parent::__construct($name,$hp,$maxHp,$img,$attackMin,$attackMax,$cry);
        $this->powerAttack = $powerAttack;
    }
    public function setPowerAttack(){
        return $this->powerAttack;
    }
    public function attack($targetObj){
        if(!mt_rand(0,4)){//5分の1の確率でパワー攻撃
            History::set($this->name.'のパワー攻撃！！');
            $targetObj->setHp($targetObj->getHp()-$this->powerAttack);
            History::set($this->powerAttack.'ポイントのダメージを受けた！');
        }else{
            parent::attack($targetObj);
        }
    }
}

interface HistoryInterface{
    public static function set($str);
    public static function clear();
}

//履歴管理クラス
class History implements HistoryInterface{
    public static function set($str){
        //セッションhistoryが作られてなければ作る
        if(empty($_SESSION['history'])) $_SESSION['history'] = '';
        $_SESSION['history'] .= $str.'<br>';
    }
    public static function clear(){
        unset($_SESSION['history']);
    }
}

//インスタンス生成
$human = new Human('小豆洗い',500,40,120);
$monsters[] = new Monster('赤おに', 100, 100, 'img/akaoni.jpg', 20, 40,Cry::SMALL);
$monsters[] = new Monster('青おに', 150, 150, 'img/aooni.jpg', 30, 60, Cry::MEDIUM);
$monsters[] = new Monster('黄おに', 200, 200, 'img/kioni.jpg', 20, 50, Cry::SMALL);
$monsters[] = new Monster('緑おに', 250, 250, 'img/midorioni.jpg', 30, 60, Cry::MEDIUM);
$monsters[] = new PowerMonster('強いおに', 300, 300, 'img/powerfuloni.jpg', 40, 80, Cry::BIG, mt_rand(60,120));

function createHuman(){
    global $human;
    $_SESSION['human'] = $human;
}

function createMonster(){
    global $monsters;
    $monster = $monsters[mt_rand(0,4)];
    History::set('▼'.$monster->getName().'が現れた！');
    $_SESSION['monster'] = $monster;
}
function init(){
    History::clear();
    History::set('>>>初期化します');
    $_SESSION['knockDownCount'] = 0;
    createHuman();
    createMonster();
}
function gameOver(){
    $_SESSION['monster'] = array();
    $_SESSION['human'] = array();
    $_SESSION['gameover'] = true;
    
}


//1.post送信されていた場合
if(!empty($_POST)){
    $attackFlg = (!empty($_POST['attack'])) ? true : false;
    $startFlg = (!empty($_POST['start'])) ? true : false;
    error_log('POSTされました');
    
    if($startFlg){
        History::set('ゲームスタート');
        init();
    }else{
        //攻撃するを押した場合
        if($attackFlg){
            //鬼に攻撃を与える
            History::set('◆'.$_SESSION['human']->getName().'の攻撃！');
            $_SESSION['human']->attack($_SESSION['monster']);
            
            
            //鬼が攻撃をする
            History::set('◆'.$_SESSION['monster']->getName().'の攻撃！');
            $_SESSION['monster']->attack($_SESSION['human']);
            
            
            //自分のHPが0以下になったらゲームオーバー
            if($_SESSION['human']->getHp() <= 0){
                gameOver();
            }else{
                //鬼のhpが0以下になったら、別の鬼を出現させる
                if($_SESSION['monster']->getHp() <= 0){
                    History::set($_SESSION['monster']->getName().'を倒した！');
                    createMonster();
                    $_SESSION['knockDownCount'] = $_SESSION['knockDownCount']+1;
                }
            }
        }else{
            //逃げるを押した場合
            History::set('逃げた！');
            createMonster();
        }
    }
    $_POST = array();
}

?>



<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>節分ゲーム</title>
        <link rel="stylesheet" href="style.css" type="text/css">
    </head>
    
    <body>
        <div class="wrapper">
            <section class="game">
                <h1 class="title">鬼は〜外！福は〜内！</h1>
                
                <?php if(empty($_SESSION)){ ?>
                   <div class="start-container">
                  
                    <h2>GAME START</h2>
                    
                    <div class="start">
                    <form method="post">
                        <input type="submit" name="start" value="ゲームスタート">
                    </form>
                    </div>
                    </div>
                <?php }elseif($_SESSION['gameover']){ ?>
                    <div class="start-container">
                  
                    <h2>GAME OVER</h2>
                    <h2>倒したおには<?php echo $_SESSION['knockDownCount']; ?>匹です</h2>
                    <div class="start">
                    <form method="post">
                        <input type="submit" name="start" value="もういちど">
                    </form>
                    </div>
                    </div>
                    <?php $_SESSION = array(); ?>
                
                
                <?php }else{ ?>
                <div class="game-container">
                 <div class="oni-container">
                   <div class="oni-table">
                    <div class="oni">
                        <img src="<?php echo $_SESSION['monster']->getImg(); ?>" alt="" class="oni-img">
                    </div>
                    </div>
                    <div class="oni-hp">
                        <p>HP:<?php echo $_SESSION['monster']->getHp(); ?>/<?php echo $_SESSION['monster']->getMaxHp(); ?></p>
                    </div>
                 </div>
                    
                 <div class="player-container">
                    <p class="oni-name"><?php echo $_SESSION['monster']->getName().'が現れた！'; ?></p>
                    <div class="balloon">
                      <p style="color:rgba(253,253,243,1);"><?php echo $_SESSION['monster']->sayCry(); ?></p>
                    </div>
                    <p class="question">豆をなげる？</p>
                    <form method="post">
                        <input type="submit" name="attack" value="なげる" style="margin-right:20px; margin-left:10px;">
                        <input type="submit" name="escape" value="にげる">
                    </form>
                 </div>
                </div>
            </section>
            <section class="log">
                <div class="log-container">
                   <div class="log-left">
                        <div class="log-box js-auto-scroll">
                        <p><?php echo (!empty($_SESSION['history'])) ? $_SESSION['history'] : ''; ?></p>
                        </div>
                    </div>
                    <div class="log-center">
                        <div class="info">
                        <p>残りの豆：<?php echo $_SESSION['human']->getHp(); ?></p>
                        <p>倒したおに：<?php echo $_SESSION['knockDownCount']; ?></p>
                        </div>
                        <div class="restart">
                        <form method="post">
                        <input type="submit" name="start" value="はじめから">
                        </form>
                        </div>
                    </div>
                    <div class="log-right">
                        <div class="mame">
                        <img src="img/mame.jpg" alt="" class="mame-img">
                        </div>
                    </div>
                </div>
                
            </section>
            
            <?php } ?>
        </div>
        
        <script src="js/vendor/jquery-3.4.1.min.js"></script>
        
        <script>
        $(function() {
        //自動スクロール
        //メッセージエリアのDOMを変数に格納
        var $scrollAuto = $('.js-auto-scroll');
        //animate関数で利用できるプロパティは数値を扱うプロパティの値を簡単に変化させることができる関数
        //scrollTop()」は、ブラウザの画面をスクロールした時の位置（スクロール量）を取得できるメソッド。引数を設定することで任意のスクロール位置まで移動させることが可能
        //scrollHeightは、あふれた(overflowした)画面上に表示されていないコンテンツを含む要素の内容の高さ
        //scrollTopの要素をscrollHeightに徐々に変化させている
        $scrollAuto.animate({
            scrollTop: $scrollAuto[0].scrollHeight
            }, 0.1);
        })
        </script>
        
    </body>
    
</html>