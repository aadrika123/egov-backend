<?php

if(!function_exists('SMSJHGOVT'))
{
	function SMSJHGOVT($mobileno, $message, $templateid=null)
    {
        if(strlen($mobileno)==10 && is_numeric($mobileno) && $templateid != NULL)
        {
            $username="uidjharsms-udd"; //username of the department
            $password="udd#@1"; //password of the department
            $senderid="JHGOVT"; //senderid of the deparment
            $message=$message; //message content
            $deptSecureKey= "d6c0ac84-59eb-4908-95aa-d1478e996c3b"; //departsecure key for encryption of message...
            $encryp_password=sha1(trim($password));
            $url = "https://msdgweb.mgov.gov.in/esms/sendsmsrequestDLT";

            $key=hash('sha512', trim($username).trim($senderid).trim($message).trim($deptSecureKey));
            $data = array(
				"username" => trim($username),
				"password" => trim($encryp_password),
				"senderid" => trim($senderid),
				"content" => trim($message),
				"smsservicetype" =>"singlemsg",
				"mobileno" =>trim($mobileno),
				"key" => trim($key),
				"templateid" => $templateid,
            );

            $fields = '';
            foreach($data as $key => $value) {
                $fields .= $key . '=' . urlencode($value) . '&';
            }
            rtrim($fields, '&');
            $post = curl_init();
            //curl_setopt($post, CURLOPT_SSLVERSION, 5); // uncomment for systems supporting TLSv1.1 only
            curl_setopt($post, CURLOPT_SSLVERSION, 6); // use for systems supporting TLSv1.2 or comment the line
            curl_setopt($post, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($post, CURLOPT_URL, $url);
            curl_setopt($post, CURLOPT_POST, count($data));
            curl_setopt($post, CURLOPT_POSTFIELDS, $fields);
            curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($post); //result from mobile seva server
            curl_close($post);
            $response = ['response'=>true, 'status'=> 'success', 'msg'=>1];
            if (strpos($result, '402,MsgID') !== false)
            {
                $response = ['response'=>true, 'status'=> 'success', 'msg'=>$result];
            }
            else
            {
                $response = ['response'=>false, 'status'=> 'failure', 'msg'=>$result];                
            }
			
			//print_r($response);
            return $response;
        }
        else
        {
            if($templateid == NULL)
              $response = ['response'=>false, 'status'=> 'failure', 'msg'=>'Template Id is required'];
            else
              $response = ['response'=>false, 'status'=> 'failure', 'msg'=>'Invalid Mobile No.'];
            return $response;
        }
	}
}
if(!function_exists('send_sms'))
{
    function send_sms($mobile,$message, $templateid)
    {
        // $res=SMSJHGOVT("7050180186", $message, $templateid);
        //print_var($message);
        // $res=SMSJHGOVT($mobile, $message, $templateid);
        return [];//$res;
    }
}
if(!function_exists('Trade'))
{
    function Trade($data=array(),$sms_for=null)
    {
        if(strtoupper($sms_for)==strtoupper('Payment done'))
        {       
          try
          {
              // Payment done with amount {#var#} for Application No {#var#}. {#var#}
              $sms = "Payment done with amount ".$data['ammount']." for Application No ".$data['application_no'].". Reference Number '".$data['ref_no']."'";
              $temp_id = "1307162359745436093";
              return array("sms"=>$sms,"temp_id"=>$temp_id,'status'=>true);              
          } 
          catch(Exception $e)
          {
            return array("sms_formate"=>"Payment done with amount {#var#} for Application No {#var#}. {#var#}",
            "discriuption"=>"1. 2 para required 
                      2. 1st para array('ammount'=>'','application_no'=>'','ref_no'=>'') sizeof 3  
                      3. 2nd para sms for ",
            "error"=>$e->getMessage(),
            'status'=>false);
          }
        }
        

        elseif(strtoupper($sms_for)==strtoupper('License expired'))
        {
            try
            { 
                // Dear Trade Owner, Your Municipal Trade License {#var#} is to be expired on {#var#}. Please renew your license to avoid legal actions. Please ignore if already done. For Details call-{#var#} {#var#}
                //$sms = "Dear Trade Owner, Your Municipal Trade License 11 is to be expired on 2022-03-01. Please renew your license to avoid legal actions. Please ignore if already done. For Details call-123 123"; 
                $sms = "Dear Trade Owner, Your Municipal Trade License ".$data['licence_no']." is to be expired on ".$data['exp_date'].". Please renew your license to avoid legal actions. Please ignore if already done. For Details call-".$data['toll_free_no1'].' '.$data['ulb_name']."";
                $temp_id = "1307162359758955377";
                return array("sms"=>$sms,"temp_id"=>$temp_id,'status'=>true);              
            } 
            catch(Exception $e)
            {
                return array("sms_formate"=>"Dear Trade Owner, Your Municipal Trade License {#var#} is to be expired on {#var#}. Please renew your license to avoid legal actions. Please ignore if already done. For Details call-{#var#} {#var#}",
                "discriuption"=>"1. 2 para required 
                      2. 1st para array('licence_no'=>'','exp_date'=>'','toll_free_no1'=>'','ulb_name'=>'') sizeof 4  
                      3. 2nd para sms for ",
                "error"=>$e->getMessage(),
                'status'=>false);
            }
        }

        elseif(strtoupper($sms_for)==strtoupper('Application Approved'))
        {
            try
            {
                // Your Application {#var#} has been approved. Your License no is {#var#}. {#var#}               
                $sms = "Your Application $data[application_no] has been approved. Your License no is $data[licence_no]. $data[ulb_name]";
                $temp_id = "1307162359751828659";
                return array("sms"=>$sms,"temp_id"=>$temp_id,'status'=>true);              
            } 
            catch(Exception $e)
            {
                return array("sms_formate"=>"Your Application {#var#} has been approved. Your License no is {#var#}. {#var#}",
                "discriuption"=>"1. 2 para required 
                      2. 1st para array('application_no'=>'','licence_no'=>'','ulb_name'=>'') sizeof 3  
                      3. 2nd para sms for ",
                "error"=>$e->getMessage(),
                'status'=>false);
            }
        }

        elseif(strtoupper($sms_for)==strtoupper('sent back'))
        {
            try
            {
                // Your Application {#var#} is sent back to you for rectification. Please rectify it and submit it shortly. RANCHI MUNICIPAL CORPORATION               
                //$sms = "Your Application $data[application_no] is sent back to $data[to] by $data[by] for rectification. Please rectify it and submit it shortly. $data[ulb_name]";
                $sms = "Your Application $data[application_no] is sent back to you for rectification. Please rectify it and submit it shortly. RANCHI MUNICIPAL CORPORATION";
                $temp_id = "1307161908232955556";
                return array("sms"=>$sms,"temp_id"=>$temp_id,'status'=>true);              
            } 
            catch(Exception $e)
            {
                return array("sms_formate"=>"Your Application {#var#} is sent back to you for rectification. Please rectify it and submit it shortly. RANCHI MUNICIPAL CORPORATION",
                "discriuption"=>"1. 2 para required 
                      2. 1st para array('application_no'=>'') sizeof 1  
                      3. 2nd para sms for ",
                "error"=>$e->getMessage(),
                'status'=>false);
            }
        }
        else
        {
            return array('sms'=>'pleas supply two para',
                          '1'=>'array()',
                          '2'=>"sms for 
                          1. Payment done
                          2. License expired
                          3. Application Approved
                          4. sent back",
                          'status'=>false
                        );
        }
        
    }
}

if(!function_exists('Water'))
{
    function Water($data=array(),$sms_for=null)
    {
        if(strtoupper($sms_for)==strtoupper('Apply Application'))
        {
            $sms="Your Application No. for Water Connection request is {#var#}. {#var#}";
            try
            {
                // Your Application No. for Water Connection request is {#var#}. {#var#}
                $sms = "Your Application No. for Water Connection request is ".$data['application_no'].". ".$data['ulb_name'];
                $temp_id = "1307162359771216938";
                return array("sms"=>$sms,"temp_id"=>$temp_id,'status'=>true);              
            } 
            catch(Exception $e)
            {
                return array("sms_formate"=>"Your Application No. for Water Connection request is {#var#}. {#var#}",
                "discriuption"=>"1. 2 para required 
                        2. 1st para array('application_no'=>'','ulb_name'=>'') sizeof 2  
                        3. 2nd para sms for ",
                "error"=>$e->getMessage(),
                'status'=>false);
            }
        }

        elseif(strtoupper($sms_for)==strtoupper('Payment done'))
        {       
          try
          {
              // Payment of Rs. {#var#} for Application No. {#var#} have been successfully done. {#var#}
              $sms = " Payment of Rs. ".$data['ammount']." for Application No. ".$data['application_no']." have been successfully done. Trans. No.".$data['ref_no']."";
              $temp_id = "1307162359771216938";
              return array("sms"=>$sms,"temp_id"=>$temp_id,'status'=>true);              
          } 
          catch(Exception $e)
          {
            return array("sms_formate"=>"Payment of Rs. {#var#} for Application No. {#var#} have been successfully done. {#var#}",
            "discriuption"=>"1. 2 para required 
                      2. 1st para array('ammount'=>'','application_no'=>'','ref_no'=>'') sizeof 3  
                      3. 2nd para sms for ",
            "error"=>$e->getMessage(),
            'status'=>false);
          }
        }
        

        elseif(strtoupper($sms_for)==strtoupper('consumer Payment'))
        {
            try
            {
                // Water User Charge of Rs. {#var#} for Consumer No. {#var#} have been successfully done. {#var#}                
                $sms = "Water User Charge of Rs. $data[ammount] for Consumer No. $data[consumer_no] have been successfully done. Trans. No.'$data[ref_no]'";
                $temp_id = "1307162359786763116";
                return array("sms"=>$sms,"temp_id"=>$temp_id,'status'=>true);              
            } 
            catch(Exception $e)
            {
                return array("sms_formate"=>"Water User Charge of Rs. {#var#} for Consumer No. {#var#} have been successfully done. {#var#}",
                "discriuption"=>"1. 2 para required 
                      2. 1st para array('ammount'=>'','consumer_no'=>'','ref_no'=>'') sizeof 3  
                      3. 2nd para sms for ",
                "error"=>$e->getMessage(),
                'status'=>false);
            }
        }

        elseif(strtoupper($sms_for)==strtoupper('Application Approved'))
        {
            try
            {
                // Your Water connection request has been approved. Your Consumer Number is {#var#}               
                $sms = "Your Water connection request has been approved. Your Consumer Number is $data[consumer_no]";
                $temp_id = "1307161908275182619";
                return array("sms"=>$sms,"temp_id"=>$temp_id,'status'=>true);              
            } 
            catch(Exception $e)
            {
                return array("sms_formate"=>"Your Water connection request has been approved. Your Consumer Number is {#var#}",
                "discriuption"=>"1. 2 para required 
                      2. 1st para array('consumer_no'=>'') sizeof 1  
                      3. 2nd para sms for ",
                "error"=>$e->getMessage(),
                'status'=>false);
            }
        }

        elseif(strtoupper($sms_for)==strtoupper('sent back'))
        {
            try
            {
                // Your Application {#var#} is sent back to you for rectification. Please rectify it and submit it shortly. RANCHI MUNICIPAL CORPORATION               
                //$sms = "Your Application $data[application_no] is sent back to $data[to] by $data[by] for rectification. Please rectify it and submit it shortly. $data[ulb_name]";
                $sms = "Your Application $data[application_no] is sent back to you for rectification. Please rectify it and submit it shortly. RANCHI MUNICIPAL CORPORATION";
                $temp_id = "1307161908232955556";
                return array("sms"=>$sms,"temp_id"=>$temp_id,'status'=>true);              
            } 
            catch(Exception $e)
            {
                return array("sms_formate"=>"Your Application {#var#} is sent back to you for rectification. Please rectify it and submit it shortly. RANCHI MUNICIPAL CORPORATION",
                "discriuption"=>"1. 2 para required 
                      2. 1st para array('application_no'=>'') sizeof 1  
                      3. 2nd para sms for ",
                "error"=>$e->getMessage(),
                'status'=>false);
            }
        }

        elseif(strtoupper($sms_for)==strtoupper('Site inspection set'))
        {
            try
            {
                // Your Site inspection Date is set on {#var#}. Please be there around the time. RANCHI MUNICIPAL CORPORATION               
                $sms = "Your Site inspection Date is set on $data[timestampe]. Please be there around the time. RANCHI MUNICIPAL CORPORATION";
                $temp_id = "1307161908281616235";
                return array("sms"=>$sms,"temp_id"=>$temp_id,'status'=>true);              
            } 
            catch(Exception $e)
            {
                return array("sms_formate"=>"Your Site inspection Date is set on {#var#}. Please be there around the time. RANCHI MUNICIPAL CORPORATION",
                "discriuption"=>"1. 2 para required 
                      2. 1st para array('timestampe'=>'') sizeof 1  
                      3. 2nd para sms for ",
                "error"=>$e->getMessage(),
                'status'=>false);
            }
        }

        elseif(strtoupper($sms_for)==strtoupper('Site inspection cancelled'))
        {
            try
            {
                // Sorry!!.. Your inspection date and time is cancelled, New date and time will be informed you shortly '.RANCHI MUNICIPAL CORPORATION               
                $sms = "Sorry!!.. Your inspection date and time is cancelled, New date and time will be informed you shortly '.RANCHI MUNICIPAL CORPORATION";
                $temp_id = "1307161908287515622";
                return array("sms"=>$sms,"temp_id"=>$temp_id,'status'=>true);              
            } 
            catch(Exception $e)
            {
                return array("sms_formate"=>"Sorry!!.. Your inspection date and time is cancelled, New date and time will be informed you shortly '.RANCHI MUNICIPAL CORPORATION",
                "discriuption"=>"1. 2 para required 
                      2. 1st para array() sizeof 0  
                      3. 2nd para sms for ",
                "error"=>$e->getMessage(),
                'status'=>false);
            }
        }
        elseif(strtoupper($sms_for)==strtoupper('Consumer Demand'))
        {
            try
            {
                //Pls pay water user charge of amount {#var#} against your consumer no. {#var#}. * Pls. ignore if already paid. If any query call us {#var#}. {#var#}               
                $sms = "Pls pay water user charge of amount ".$data['amount']." against your consumer no. ".$data['consumer_no'].". * Pls. ignore if already paid. If any query call us ".$data['toll_free_no1'].'. '.$data['ulb_name'];
                $temp_id = "1307162359780171746";
                return array("sms"=>$sms,"temp_id"=>$temp_id,'status'=>true);              
            } 
            catch(Exception $e)
            {
                return array("sms_formate"=>"Pls pay water user charge of amount {#var#} against your consumer no. {#var#}. * Pls. ignore if already paid. If any query call us {#var#}. {#var#}",
                "discriuption"=>"1. 2 para required 
                      2. 1st para array('amount'=>'','consumer_no'=>'','toll_free_no1'=>'','ulb_name'=>'') sizeof 4  
                      3. 2nd para sms for ",
                "error"=>$e->getMessage(),
                'status'=>false);
            }
        }

        else
        {
            return array('sms'=>'pleas supply two para',
                          '1'=>'array()',
                          '2'=>"sms for 
                          1. Payment done
                          2. consumer Payment
                          3. Application Approved
                          4. sent back
                          5. Site inspection set
                          6. Site inspection cancelled
                          7. Apply Application",                          
                          'status'=>false
                        );
        }
        
    }
}

if(!function_exists("Property"))
{
    function Property($data=array(),$sms_for=null)
    {
        if(strtoupper($sms_for)==strtoupper('Holding Demand'))
        {
            try
            {
                //Holding Tax of Rs{#var#} upto QTR {#var#} is due for Holding No: {#var#} {#var#}               
                $sms = "Holding Tax of Rs ".$data["amount"]." upto QTR ".$data["qtr"]." is due for Holding No: ".$data["holding_no"]." ".$data['ulb_name']."";
                $temp_id = "1307162359693822172";
                return array("sms"=>$sms,"temp_id"=>$temp_id,'status'=>true);              
            } 
            catch(Exception $e)
            {
                return array("sms_formate"=>"Holding Tax of Rs{#var#} upto QTR {#var#} is due for Holding No: {#var#} {#var#}",
                "discriuption"=>"1. 2 para required 
                      2. 1st para array('amount'=>'','qtr'=>'','holding_no'=>'','ulb_name'=>'') sizeof 4  
                      3. 2nd para sms for ",
                "error"=>$e->getMessage(),
                'status'=>false);
            }
        }
        elseif(strtoupper($sms_for)==strtoupper('Holding Demand Res'))
        {
            try
            {
                //Dear {#var#}, pay your against Holding No. {#var#} and Ward No {#var#} amount. {#var#}, * Please ignore if already paid. {#var#}               
                // $sms = "Holding Tax of Rs ".$data["amount"]." upto QTR ".$data["qtr"]." is due for Holding No: ".$data["holding_no"]." ".$data['ulb_name']."";
                $sms = "Dear ".$data["owner_name"].", pay your against Holding No. ".$data["holding_no"]." and Ward No ".$data["ward_no"]." amount. ".$data["amount"].", * Please ignore if already paid. ".$data['ulb_name']."";
                $temp_id = "1307162359687707022";
                return array("sms"=>$sms,"temp_id"=>$temp_id,'status'=>true);              
            } 
            catch(Exception $e)
            {
                return array("sms_formate"=>"Dear {#var#}, pay your against Holding No. {#var#} and Ward No {#var#} amount. {#var#}, * Please ignore if already paid. {#var#}",
                "discriuption"=>"1. 2 para required 
                      2. 1st para array('owner_name'=>'','holding_no'=>'','ward_no'=>'','amount'=>'','ulb_name'=>'') sizeof 5  
                      3. 2nd para sms for ",
                "error"=>$e->getMessage(),
                'status'=>false);
            }
        }
        else
        {
            return array('sms'=>'pleas supply two para',
                          '1'=>'array()',
                          '2'=>"sms for 
                          1. Holding Demand
                          2. Holding Demand Res",                          
                          'status'=>false
                        );
        }
    }
}

if(!function_exists("OTP"))
{
    function OTP($data=array(),$sms_for=null)
    {
        if(strtoupper($sms_for)==strtoupper('Application OTP'))
        {
            try
            {
                #OTP Code. {#var#} for Your Application No {#var#} {#var#}              
                $sms = "OTP Code.".$data["otp"]." for Your Application No ".$data["application_no"]." ".$data["ulb_name"]."";
                $temp_id = "1307162359726658524";
                return array("sms"=>$sms,"temp_id"=>$temp_id,'status'=>true);              
            } 
            catch(Exception $e)
            {
                return array("sms_formate"=>"OTP Code. {#var#} for Your Application No {#var#} {#var#}",
                "discriuption"=>"1. 2 para required 
                      2. 1st para array('otp'=>'','application_no'=>'','ulb_name'=>'') sizeof 3  
                      3. 2nd para sms for ",
                "error"=>$e->getMessage(),
                'status'=>false);
            }
        }
        elseif(strtoupper($sms_for)==strtoupper('Holding Online Payment OTP'))
        {
            try
            {
                #Dear Citizen, your OTP for online payment of Holding Tax for {#var#} is INR {#var#}. This OPT is valid for {#var#} minutes only.              
                $sms = "Dear Citizen, your OTP for online payment of Holding Tax for ".$data["holding_no"]." is INR ".$data["amount"].". This OPT is valid for ".$data["validity"]." minutes only.";
                $temp_id = "1307161908198113240";
                return array("sms"=>$sms,"temp_id"=>$temp_id,'status'=>true);              
            } 
            catch(Exception $e)
            {
                return array("sms_formate"=>"Dear Citizen, your OTP for online payment of Holding Tax for {#var#} is INR {#var#}. This OPT is valid for {#var#} minutes only.",
                "discriuption"=>"1. 2 para required 
                      2. 1st para array('holding_no'=>'','amount'=>'','validity'=>'') sizeof 3  
                      3. 2nd para sms for ",
                "error"=>$e->getMessage(),
                'status'=>false);
            }
        }
        else
        {
            return array('sms'=>'pleas supply two para',
                          '1'=>'array()',
                          '2'=>"sms for 
                          1. Application OTP
                          2. Holding Online Payment OTP",                          
                          'status'=>false
                        );
        }
    }
}