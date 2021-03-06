<?php
    /**
     * 该代码仅供学习交流，请合理使用，出现任何纠纷与作者无关
     * 疫情期间：请如实报告自己的身体情况
     */
    ini_set('date.timezone','Asia/Shanghai');
    require("email.php");
    require("getconfig.php");

    /**
     * @desc 获取大学印象用户列表
    **/
    $data = getconfig::user("dxyx")[0];
    for ($i = 0; $i < count($data); $i++) {

        $e = $data[$i];

        $sno = $e["sno"];          //登录学号
        $pwd = $e["pwd"];          //登录密码 初始密码为身份证后6位

        $curlocation = $e["curlocation"]; //当前位置
        $goout = $e["goout"];       //3日内是否有出行计划  1有 0无
        $hp = $e["hp"];             //健康状况  0正常  1异常
        $ncp = $e["ncp"];           //当前是否有新冠肺炎症状  0否  1是
        $isncp = $e["isncp"];       //当前是否为疑似或确诊病例  0否  1确诊  2疑似
        $touchncp = $e["touchncp"]; //15日内是否接触过ncp患者  0否  1是
        $hubei = $e["hubei"];       //15日内是否去过湖北  0否  1是
        $ps = $e["ps"];             //备注


        //是否需要邮件提示
        $isEmail = $e["isEmail"];      //开启邮件提示
        $smtpServer = $e["smtpServer"];//发送者：smtp服务器地址
        $smtpPort = $e["smtpPort"];    //发送者：端口号
        $email = $e["email"];          //发送者：email账号
        $password = $e["password"];    //发送者：email密码(qq邮箱需要授权码)
        $name = $e["name"];            //发送者：名称
        $reName = $e["reName"];        //接收者：名称
        $reEmail = $e["reEmail"];      //接收者：email 可以填发送者email，相当于自己给自己发邮件
        $title = "健康日报自动填写完成(" . date('m-d') . ")";     //邮件标题

        //开始自动提交
        auto($sno, $pwd, $curlocation, $goout, $hp, $ncp, $isncp, $touchncp, $hubei, $ps, $isEmail, $smtpServer, $smtpPort, $email, $password, $name, $reName, $reEmail, $title);
    }
    exit();


    /**
     * 自动提交脚本
     */
    function auto($sno, $pwd, $curlocation, $goout, $hp, $ncp, $isncp, $touchncp, $hubei, $ps, $isEmail, $smtpServer, $smtpPort, $email, $password, $name, $reName, $reEmail, $title){
        //获取token
        $curl =curl_init("https://www.dxever.com/Wxminiprog/Disease/login");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, "studno=".$sno."&password=".$pwd);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_NOBODY, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        $result = curl_exec($curl);
        curl_close($curl);
        $json = json_decode($result, true);
        if($isEmail && $json['meta']['code'] != 200){
            sendEmail($isEmail, $smtpServer, $smtpPort, $email, $password, $name, $reName, $reEmail, $json['meta']['message'], $json['meta']['message']);
            return false;
        }else{
            $token = $json['data'];
            //填写数据
            $post = "token=".$token
                ."&curlocation=".$curlocation
                ."&goout=".$goout
                ."&hp=".$hp
                ."&ncp=".$ncp
                ."&isncp=".$isncp
                ."&touchncp=".$touchncp
                ."&hubei=".$hubei
                ."&ps=".$ps;
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, "https://www.dxever.com/Wxminiprog/Disease/addLog");
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_NOBODY, false);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/x-www-form-urlencoded',
                'Content-Length: ' . strlen($post),
                'Host: www.dxever.com',
                'Origin: https://www.dxever.com',
                'Referer: https://www.dxever.com/fei/delete/ncp/main.html')
            );
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
            $result = curl_exec($curl);
            curl_close($curl);

            $json = json_decode($result, true);
            
            if($isEmail && $json['meta']['code'] != 200){
                sendEmail($isEmail, $smtpServer, $smtpPort, $email, $password, $name, $reName, $reEmail, $json['meta']['message'], $json['meta']['message']);
                return false;
            }else{
                //邮件发送
                if($isEmail && $result){
                    $body = "<h2>今日提交记录</h2>"."<ol>"
                            ."<li>当前位置：".$curlocation."</li>"
                            ."<li>3日内是否有出行计划（1有 0无）：".$goout."</li>"
                            ."<li>健康状况（0正常 1异常）：".$hp."</li>"
                            ."<li>当前是否有新冠肺炎症状（0否 1是）：".$ncp."</li>"
                            ."<li>当前是否为疑似或确诊病例（0否 1确诊 2疑似）：".$isncp."</li>"
                            ."<li>15日内是否接触过ncp患者（0否 1是）：".$touchncp."</li>"
                            ."<li>15日内是否去过湖北（0否 1是）：".$hubei."</li>"
                            ."<li>备注：".$ps."</li>"
                            ."</ol>"
                            ."<i>提交时间：".date('Y-m-d H:i:s')."</i>";
                    sendEmail($isEmail, $smtpServer, $smtpPort, $email, $password, $name, $reName, $reEmail, $title, $body); 
                    return true;
                }
                if($isEmail && !$result){
                    sendEmail($isEmail, $smtpServer, $smtpPort, $email, $password, $name, $reName, $reEmail, "服务器出错啦", "请联系管理员查看");
                    return false;
                }
            }
        }
    }
    
?>
