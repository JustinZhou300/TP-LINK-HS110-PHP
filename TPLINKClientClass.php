<?php

$cmdList = array(
    //Reference: https://github.com/softScheck/tplink-smartplug/blob/master/tplink-smarthome-commands.txt
        "on"=>'{"system":{"set_relay_state":{"state":1}}}',
        "off"=>'{"system":{"set_relay_state":{"state":0}}}',
        "info"=>'{"system":{"get_sysinfo":null}}',
        "emeter"=>'{"emeter":{"get_realtime":{}}}'
);
        
        
$smartbulbCmdList = array(
    //Reference: https://github.com/briandorey/tp-link-LB130-Smart-Wi-Fi-Bulb/blob/master/protocols.md
    "info"=>'{"system":{"get_sysinfo":null}}',
    "on" =>'{"smartlife.iot.smartbulb.lightingservice":{"transition_light_state":{"ignore_default":0,"mode":"normal","on_off":1,"transition_period":0}}}',    
    "off" =>'{"smartlife.iot.smartbulb.lightingservice":{"transition_light_state":{"ignore_default":0,"mode":"normal","on_off":0,"transition_period":0}}}',
    "light_info" => '{"smartlife.iot.smartbulb.lightingservice":{"get_light_details":{}}}',
    "reboot"=>'{"smartlife.iot.common.system":{"reboot":{"delay":1}}}',
    "emeter"=>'{"smartlife.iot.common.emeter":{"get_realtime":{}}}',
    "transition_light" => array( "smartlife.iot.smartbulb.lightingservice" => array("transition_light_state" => NULL )) 
);

$smartbulbLightSettings = array("brightness"=>0,"transition_period"=>500) ;

/*
 valid options for "$smartbulbLightSettings":
        "ignore_default",
        "mode",
        "on_off", 
        "brightness", 0-100
        "color_temp", (for LB120:2700-6500K, LB130:2500-9000K)
        "hue" (for LB130 only?:0-360)
        "saturation" (for LB130 only?:0-100)
        "transition_period" in ms
*/

define("TPLINKPORT",9999);

class TPLINKClient{

private $UDP;
private $IP;
public $socket;

private $command;
private $sendMessage;

public $error;
public $responses;

    public function __construct($UDP,$IP){
        $this->UDP = $UDP;
        $this->IP = $IP;
        $this->socket = socket_create(AF_INET, $UDP ? SOCK_DGRAM : SOCK_STREAM, $UDP ? SOL_UDP : SOL_TCP);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 3, 'usec' => 0));
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array("sec"=> 3,"usec"=>0));
    }

    public function sendReceive($command){
        $this->error = NULL;
        $this->command = $command;
        $this->sendMessage = self::encrypt($this->command,$this->UDP);
        $this->UDP ? $this->UDPsendReceive() : $this->TCPsendReceive();
        return ($this->error === NULL) ? $this->responses : $this->error;
    }

    private function TCPsendReceive(){
        if(!@socket_connect($this->socket,$this->IP,TPLINKPORT)){
            $this->error = [['-','Unable to connect socket: '. socket_strerror(socket_last_error()) . PHP_EOL]];
            return;
        } 
        if (@socket_write($this->socket, $this->sendMessage ,strlen($this->sendMessage)) === false){
            $this->error = [['-','Unable to write socket: '. socket_strerror(socket_last_error()) . PHP_EOL]];
            return;
        } 
        $response =  socket_read($this->socket , 1024);
        $this->responses = array(array($this->IP, self::decrypt($response,false)));
    }

    private function UDPSendReceive(){
        if (@!socket_bind($this->socket,0,0)) {
            $this->error = [['-','Unable to bind socket: '. socket_strerror(socket_last_error()) . PHP_EOL]];
            return; 
        }

        if (@!socket_set_option($this->socket,SOL_SOCKET,SO_BROADCAST,1)){
            $this->error = [['-','Unable to set socket option: '. socket_strerror(socket_last_error()) . PHP_EOL]]; 
            return;
        }  //Enable Broadcasting
        $sendResult = socket_sendto($this->socket,$this->sendMessage,strlen($this->sendMessage),0,$this->IP,TPLINKPORT);
        $responses = array();
        $buf; 
        $socket_address;
        $socket_port;
        while(true) {
            $ret = @socket_recvfrom($this->socket,$buf,1024,0,$socket_address,$socket_port);
            if($ret === false) break;
            array_push($responses, array($socket_address,self::decrypt($buf,true)));
          }
          $this->responses = $responses;
    }

    static public function encrypt(string $command,bool $udp){
        $key = (int) 171;
        $output = '';
        for($i = 0 ; $i <strlen($command) ;$i++){
            $temp = $key ^ ord($command[$i]);
            $key = $temp;
            $output .= pack('C', $temp);
        }
        if ($udp) {
            return $output;
        }
        $lenChar = pack('C',strlen($command));
        $padding = str_pad($lenChar, 4,"\0",STR_PAD_LEFT); 
        return  $padding . $output;
    }

    static public function decrypt(string $message,bool $udp){
        $key = (int) 171;
        $output = '';
        for($i = $udp ? 0:4 ; $i < strlen($message) ;$i++){
            $temp = $key ^ ord($message[$i]) ;
            $key = ord($message[$i]);
            $output .= pack('C', $temp);
        }
        return  $output;
    }

    public function closeSocket(){
        socket_close($this->socket);
    }
    
}

?>