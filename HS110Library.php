<?php

define("HS110PORT",9999);

$cmdList = array(
//Reference: https://github.com/softScheck/tplink-smartplug/blob/master/tplink-smarthome-commands.txt
    "on"=>'{"system":{"set_relay_state":{"state":1}}}',
    "off"=>'{"system":{"set_relay_state":{"state":0}}}',
    "info"=>'{"system":{"get_sysinfo":null}}',
    "emeter"=>'{"emeter":{"get_realtime":{}}}'
    );

function createClientSocket($UDP) {
    //Creates either a TCP or UDP socket 
    $HS110Socket =  socket_create(AF_INET, $UDP ? SOCK_DGRAM : SOCK_STREAM, $UDP ? SOL_UDP : SOL_TCP) ;
    socket_set_option($HS110Socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 3, 'usec' => 0));
    return $HS110Socket;
}

function encrypt(string $command,bool $udp){
    //Commands sent to the HS110 must be encrypted using a autokey cipher
    //Encryption method can be found by decompiling the Kasa App
    //Reference: https://www.softscheck.com/en/reverse-engineering-tp-link-hs110/
    //TCP encrypted messages need to be padded with 3 '\0' bytes and a message length byte
    //UDP messages do not have padding
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

function decrypt(string $message,bool $udp){
    //Commands sent to the HS110 must be decrypted using a autokey cipher
    //TCP encrypted messages have 4 bytes of padding at the start of the message
    //UDP messages do not have padding
    $key = (int) 171;
    $output = '';
    for($i = $udp ? 0:4 ; $i < strlen($message) ;$i++){
        $temp = $key ^ ord($message[$i]) ;
        $key = ord($message[$i]);
        $output .= pack('C', $temp);
    }
    return  $output;
}

function TCPsendReceive($socket, string $sendMessage,string $IP){
    //Sends a TCP message to the smartplug and receives reply
    if(!socket_connect($socket,$IP,HS110PORT)){
        return 'Unable to connect socket: '. socket_strerror(socket_last_error()) . PHP_EOL;
    } 
    $sendResult = socket_write($socket, $sendMessage ,strlen($sendMessage)) ;
    $response =  socket_read($socket , 1024);
    return array($IP,$response);
}

function UDPSendReceive($socket, string $sendMessage,string $IP){
    //Sends a UDP message to the smartplug and receives reply
    if (!socket_bind($socket,0,0)) {
        return 'Unable to bind socket: '. socket_strerror(socket_last_error()) . PHP_EOL;
    }
    if(!socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,array("sec"=>3,"usec"=>0))){
        return 'Unable to set socket option: '. socket_strerror(socket_last_error()) . PHP_EOL; 
    } //Set timeout to 3 secs
    if (!socket_set_option($socket,SOL_SOCKET,SO_BROADCAST,1)){
        return 'Unable to set socket option: '. socket_strerror(socket_last_error()) . PHP_EOL; 
    }  //Enable UDP Broadcasting
    $sendResult = socket_sendto($socket,$sendMessage,strlen($sendMessage),0,$IP,HS110PORT);
    $responses = array();
    $buf; 
    $socket_address;
    $socket_port;
    while(true) {
        $ret = @socket_recvfrom($socket,$buf,1024,0,$socket_address,$socket_port);
        if($ret === false) break;
        array_push($responses, array( $socket_address,$buf)  ) ;
      }
    return $responses;
}

?>