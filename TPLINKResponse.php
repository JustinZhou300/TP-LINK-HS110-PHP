<?php
include 'TPLINKClientClass.php' ;
if (isset($_POST["IP"])) {
    $useUDP = (bool) $_POST["transport"];
    $TPLINKSocket = new TPLINKClient($useUDP,$_POST["IP"]);
    switch($_POST["Commands"]){
        case "custom":
            $sendMessage = $_POST["customInput"];
            break;
        case "transition_light":
            $smartbulbLightSettings["brightness"] =  (int) $_POST["Brightness"] ;
            $temp = $smartbulbCmdList["transition_light"];
            $temp["smartlife.iot.smartbulb.lightingservice"]["transition_light_state"] = $smartbulbLightSettings;
            $sendMessage =  json_encode($temp);
            break;
        default:
         $sendMessage = ($_POST["Device"] == 0 )  ? $cmdList[$_POST["Commands"]] : $smartbulbCmdList[$_POST["Commands"]]  ;
        break;
    }

    $responses =  $TPLINKSocket->sendReceive($sendMessage);
    echo $jsonResponses = json_encode($responses);
    $TPLINKSocket->closeSocket();
    unset($TPLINKSocket);
}

?>