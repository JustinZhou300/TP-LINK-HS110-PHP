<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>HS110 Client</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" media="screen" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.0/normalize.min.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="https://cdnjs.cloudflare.com/ajax/libs/skeleton/2.0.4/skeleton.min.css" />
</head>
<body>

<?php
include 'HS110Library.php' ;
?>

<div class="container container_margin">
    <h1>HS110 Client</h1>
    <p>An api for controlling the TP-Link HS110 Smartplug written in PHP </p> 
    <form action="HS110Client.php" method="post">
        <div class ="row">
        <div class ="six columns">    
            <label for="IP">IP Address</label>
            <input class="u-full-width" type ="text" placeholder="eg: 192.168.0.2" name="IP" <?php if(isset($_POST["submit"])) { echo "value =" .$_POST["IP"]; }?> >
        </div>
        <div class ="three columns">
                <label for ="transport">Protocol</label>
                <select class="u-full-width" name = "transport">
                    <option value="0">TCP</option>
                    <option value="1">UDP</option>
                </select>
            </div>  
        </div>

        <div class="row" >
            <div class ="six columns">
            <label for = "Commands"> Command </label>
                <select class="u-full-width" name = "Commands" id="commands" onchange ="checkcustomInput()">
                    <option value="info">Get Info</option>
                    <option value="on">On</option>
                    <option value="off">Off</option>
                    <option value="emeter">Check Energy Usage</option>
                    <option value="custom">Custom</option>
                </select>
            </div> 
        </div>

        <div class ="row" id="customInputRow">
            <div class ="six columns">    
                <label for="customInput">Custom Command</label>
                <input class="u-full-width" type ="text" placeholder ='eg: {"system":{"get_sysinfo":null}}' name="customInput">
            </div>
        </div>

        <div class ="row" >
            <button class="button-primary" type ="submit" name="submit"> Submit </button>
        </div>
    </form>
 </div>

<div class="container text container_margin">
<h5> Response</h5>
<table class="u-full-width">
  <thead >
    <tr>
      <th style ="width: 150px"> IP Address</th>
      <th>Message</th>
    </tr>
  </thead>
  <tbody>
<?php

if (isset($_POST["IP"])) {
    $UDP = (bool) $_POST["transport"];
    $HS110Socket = createClientSocket($UDP);
    $sendMessage = ($_POST["Commands"] === "custom") ? encrypt($_POST["customInput"],$UDP) : encrypt($cmdList[$_POST["Commands"]],$UDP);
    $responses = $UDP ? UDPSendReceive($HS110Socket, $sendMessage, $_POST["IP"]): TCPsendReceive($HS110Socket, $sendMessage, $_POST["IP"]);
    if($UDP){
            foreach($responses as $response){
                echo "<tr> <td>" . $response[0] . "</td> <td> " . decrypt($response[1],$UDP) . " </td> </tr>";
            }
    }else{
        echo "<tr> <td>" . $responses[0] . "</td> <td> " . decrypt($responses[1],$UDP) . " </td> </tr>";
    }
}
?>
        </tbody>
    </table>
</div>

</body>
</html>

<script>

const customInputRow =  document.getElementById("customInputRow");
customInputRow.style.display = "none";

function checkcustomInput(){ 
    const commands = document.getElementById("commands") ;
    if (commands.value === "custom"){
        customInputRow.style.display = "block";
    }else{
        customInputRow.style.display = "none";
    }
}

</script>

<style>
.text{
  word-break:break-all;
}
.container_margin{ 
margin-top: 30px
}
</style>