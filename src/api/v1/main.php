<?php

//
// test
//
$app->post('/test', function() use ($app) {
    $db = new DbHandler();
    mysqli_report(MYSQLI_REPORT_STRICT);
    try{
            $db->startTransaction();
            $number1 = 1;
            $result_1 = $db->insertQuery("insert into test1(text, number, reguser, regdate) values('qwerty1', $number1, 1, '2017-09-07')");
            $number2 = $result_1;
            $result_2 = $db->insertQuery("insert into test2(text, number, reguser, regdate) values('qwerty2', $number2, 1, '2017-09-07')");
            $number3 = $result_2;
            $result_3 = $db->insertQuery("insert into test3(text, number, reguser, regdate) values('qwerty3', $number3, 1, '2017-09-07')");
            
            if ($result_1 != NULL && $result_2 != NULL && $result_3 != NULL) {//
                        $db->commitTransaction();
                        $response["status"] = "success";
                        $response["message"] = "";
                        
                        echoResponse(200, $response);

                } else {
                    $db->rollbackTransaction();
                        $response["status"] = "error";
                        $response["message"] = "Хэрэглэгч бүртгэлгүй байна.";
                        echoResponse(201, $response);
                }
    }
    catch (Exception $e) {
            $db->rollbackTransaction();
            $response["status"] = "error";
            $response["message"] = $e->getMessage();
            echoResponse(201, $response);
    }
});

$app->post('/sendsms', function() use ($app) {
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];
    $r = json_decode($app->request->getBody());
    $string_number = $r->numbers;
    $txt = $r->txt;

    $number_array = explode(PHP_EOL, $string_number);
    foreach ($number_array as $number) {
       $db->sendMSG($number, $txt, $userid);
    }
});

//
// getpdata
//
$app->post('/getResultCoagulo', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $ubchtunid = $r->ubchtunid;
    $mid = $r->id;
    $result = $db->getOneRecord("select m.ubchtunid, u.systemcode, u.lastname, u.firstname, u.rd, u.mobile, m.isResCoagulo, m.isPaidCoagulo, m.isMsgCoagulo, m.id, c.PTSEC, c.PTINR, c.aPTT, c.Fibrinogen, m.date
                                from mrlabrecord m inner join ubchtun_main u on u.id = m.ubchtunid left join sh_coagulogramm c on c.ubchtunid = m.ubchtunid and c.sourcekey = 'mrlabrecord' and c.sourceid = m.id
                                where m.isResCoagulo = 'Y' and m.isPaidCoagulo = 'Y' and m.ubchtunid = $ubchtunid and m.id = $mid");
     
     if ($result != NULL) {
                $response["status"] = "success";
                $response["message"] = "";
                foreach($result as $column => $value)
                {
                    $response[$column] = $value;
                }
                echoResponse(200, $response);
        } else {
                $response["status"] = "error";
                $response["message"] = "Хэрэглэгч бүртгэлгүй байна.";
                echoResponse(201, $response);
        }
});
//
// saveResultCoagulo
//
$app->post('/saveResultCoagulo', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $patient_data = $r->pdata;
   // verifyRequiredParams(array('weight', 'height', 'corediagnosis'),$patient_data);
    $db = new DbHandler();
    $session = $db->getSession();

    $ubchtunid = $patient_data->ubchtunid;
    $mid = $patient_data->mid;
    $patient_data->sourceid = $mid;
    $patient_data->sourcekey = 'mrlabrecord';
    $patient_data->address = 1;
    $patient_data->date = date('Y-m-d');
    $where_clause = "upper(ubchtunid)=upper('$ubchtunid') and sourcekey = 'mrlabrecord' and sourceid = $mid";

        $tabble_name = "sh_coagulogramm";
        $column_names = array('date', 'PTSEC', 'PTINR', 'aPTT', 'Fibrinogen', 'sourcekey', 'sourceid', 'ubchtunid', 'address');

  $isUserExists = $db->getOneRecord("select 1 from sh_coagulogramm where upper(ubchtunid)=upper('$ubchtunid') and sourcekey = 'mrlabrecord' and sourceid = $mid");
    if(!$isUserExists){
           array_push($column_names, 'reguser', 'regdate');
            $patient_data->reguser = "client";
            $patient_data->regdate = date('Y-m-d H:i:s');
           $result = $db->insertIntoTable($patient_data, $column_names, $tabble_name);
           $upresult = $db->updateQuery("UPDATE mrlabrecord SET isResCoagulo = 'Y' where id = $mid");
    } else
    {
         $result = $db->updateTable($patient_data, $column_names, $tabble_name, $where_clause );
    }
        if ($result != NULL) {
                $response["status"] = "success";
                $response["message"] = "Амжилттай хадгаллаа.";
                echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Шинээр үүсгэхэд алдаа гарлаа.";
            echoResponse(201, $response);
        }

});
//
// getmrResList
//
$app->post('/getmrResList', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $testtype = $r->testtype;
    $where = $r->where;

    $result = $db->getRecord("select m.ubchtunid, u.systemcode, u.lastname, u.firstname, u.rd, u.mobile, m.isResCoagulo, m.isBlCoagulo, m.isPaidCoagulo, m.isPBlCoagulo, m.isMsgCoagulo, m.id, c.PTSEC, c.PTINR, c.aPTT, c.Fibrinogen, m.date
                            from mrlabrecord m inner join ubchtun_main u on u.id = m.ubchtunid 
                            left join sh_coagulogramm c on c.ubchtunid = m.ubchtunid and c.sourcekey = 'mrlabrecord' and c.sourceid = m.id
                            where m.ispaidCoagulo = 'Y' and m.isCoagulo = 'Y' order by m.isResCoagulo, m.date desc");
     if($result != NULL){
              $response["status"] = "success";
              $response["message"] = "";
              $response['totalcount'] = 0;
              $rows =  array();
              while($r =$result->fetch_assoc())
             {
              $rows[] = $r;
             }
             if(count($rows) > 0)
              {
                $response['totalcount'] = count($rows);
                $response['data'] = json_encode($rows);
              }
            else $response['data'] = [];
              echoResponse(200, $response);
      }else{
          $response["status"] = "error";
          $response["message"] = "Утга авахад алдаа гарлаа!";
          echoResponse(201, $response);
      }
});

//
// getpdata
//
$app->post('/getpdata', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $systemcode = $r->systemcode;

   if(ctype_digit($systemcode)){
       $result = $db->getOneRecord("select * from patient_data where ubchtunid = (select id from ubchtun_main where systemcode = $systemcode)");
     } else {
       $result = $db->getOneRecord("select * from patient_data where ubchtunid = (select id from ubchtun_main where upper(rd) = upper('$systemcode'))");
     }
     if ($result != NULL) {
                $response["status"] = "success";
                $response["message"] = "";
                foreach($result as $column => $value)
                {
                    $response[$column] = $value;
                }
                echoResponse(200, $response);
        } else {
                $response["status"] = "error";
                $response["message"] = "Хэрэглэгч бүртгэлгүй байна.";
                echoResponse(201, $response);
        }
});

//
// savepdata
//
$app->post('/savepdata', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $patient_data = $r->pdata;
   // verifyRequiredParams(array('weight', 'height', 'corediagnosis'),$patient_data);
    $db = new DbHandler();
    $session = $db->getSession();

    $ubchtunid = $patient_data->ubchtunid;
    $where_clause = "upper(ubchtunid)=upper('$ubchtunid')";

        $tabble_name = "patient_data";
        $column_names = array( 'iscoffee', 'istobacco', 'isalcohol', 'rd', 'ubchtunid', 'height', 'weight', 'coffee', 'tobacco', 'alcohol','corediagnosis','emergency','seconddiagnosis','history_mother','history_father',
       'history_siblings','history_offspring','history_spouse','additional_history','hepb','hepc','hepd','hepother');

  $isUserExists = $db->getOneRecord("select 1 from patient_data where upper(ubchtunid)=upper('$ubchtunid')");
    if(!$isUserExists){
           array_push($column_names, 'reguser', 'regdate');
            $patient_data->reguser = "client";
            $patient_data->regdate = date('Y-m-d H:i:s');
           $result = $db->insertIntoTable($patient_data, $column_names, $tabble_name);
    } else
    {
         $result = $db->updateTable($patient_data, $column_names, $tabble_name, $where_clause );
    }
        if ($result != NULL) {
                $response["status"] = "success";
                $response["message"] = "Амжилттай хадгаллаа.";
                echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Шинээр үүсгэхэд алдаа гарлаа.";
            echoResponse(201, $response);
        }

});

//
//getSelfInfo
//
$app->post('/getSelfInfo', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $systemcode = $r->systemcode;

    if(ctype_digit($systemcode)){
       $result = $db->getOneRecord("select * from ubchtun_main where systemcode = $systemcode");
     } else {
       $result = $db->getOneRecord("select * from ubchtun_main where upper(rd)=upper('$systemcode')");
     }
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';
        $response['ubchtunid'] = $result['id'];
        $response['lastname'] = $result['lastname'];
        $response['firstname'] = $result['firstname'];
        $response['rd'] = $result['rd'];
        $response['mobile'] = $result['mobile'];
        $response['email'] = $result['email'];
        $response['systemcode'] = $result['systemcode'];
    }else {
            $response['status'] = "error";
            $response['message'] = 'Хэрэглэгч бүртгэлгүй байна.';
        }
    echoResponse(200, $response);
});
//
// getResultPatient
//
$app->post('/getResultPatient', function() use ($app) {
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $r = json_decode($app->request->getBody());
    $ubchtunid = $r->ubchtunid;

   $result = $db->getRecord('select AR.* from (select id, date, "BIO" test, CONCAT(\'{"TBIL":"\', TBIL,\'","DBIL":"\', DBIL, \'","TC":"\', TC, \'", "ALP":"\', ALP, \'", "TP":"\', TP, \'", "Lipase":"\', Lipase, \'", "Tryglycerides":"\', Tryglycerides, \'", "ALB":"\', ALB, \'", "AST":"\', AST, \'", "ALT":"\', ALT, \'", "GGT":"\', GGT, \'", "LDH":"\', LDH, \'", "LDL":"\', LDL, \'", "HDL":"\', HDL, \'", "GLU":"\', GLU, \'", " CREA":"\', CREA, \'", "BUN":"\', BUN, \'", "NH3":"\', NH3, \'", "UA":"\', UA, \'", "K":"\', K, \'", "P":"\', P, \'", "CI":"\', CI, \'", "Ca":"\', Ca, \'", "Na":"\', Na, \'", "Mg":"\', Mg, \'", "Fe":"\', Fe, \'", "AMY":"\', AMY, \'", "Insulin":"\', Insulin, \'", "HemoglobinA1c":"\', HemoglobinA1c, \'", "FER":"\', FER,\'"}\') detail from sh_biohimi where ubchtunid = '.$ubchtunid.' and sourcekey = "livercenter"
   union
   select id, date, "BLOOD" test, CONCAT(\'{"lekotsin":"\',lekotsin, \'", "eritrotsit":"\', eritrotsit, \'", "hemoglobin":"\', hemoglobin, \'", "gematokrit":"\', gematokrit, \'", "MCV":"\', MCV, \'", "MCH":"\', MCH, \'", "MCHC":"\', MCHC, \'", "trombotsit":"\', trombotsit, \'", "UETH":"\', UETH, \'", "NEU":"\', NEU, \'", "limfotsit":"\', limfotsit, \'", "monotsit":"\', monotsit, \'", "eozinofil":"\', eozinofil, \'", "bazofil":"\', bazofil, \'", "MPV":"\', MPV, \'", "PDWsd":"\', PDWsd, \'", "PDWcv":"\', PDWcv, \'", "PLCR":"\', PLCR, \'", "PLCC":"\', PLCC, \'", "PCT":"\', PCT, \'", "NEU_N":"\', NEU_N, \'", "LYM_N":"\', LYM_N, \'", "MON_N":"\', MON_N, \'", "EO_N":"\', EO_N, \'", "BAS_N":"\', BAS_N, \'", "RDWsd":"\', RDWsd, \'", "RDWcv":"\', RDWcv,\'"}\') detail from sh_blood where ubchtunid = '.$ubchtunid.'  and sourcekey = "livercenter"
   union
   select n.id, n.date, "PCR HBV" test, CONCAT(\'{"HBV_DNA":"\', COALESCE(n.HBV_DNA,""), \'", "testtype":"\', COALESCE(m.testtype,""), \'", "runtime":"\', COALESCE(m.runtime,""), \'",  "platename":"\', COALESCE(m.platename,""), \'", "dwplatename":"\', COALESCE(m.dwplatename,""), \'", "serlot":"\', COALESCE(m.serlot,""), \'", "serexpiration":"\', COALESCE(m.serexpiration,""), \'", "sectime":"\', COALESCE(m.sectime,""), \'", "mmactime":"\', COALESCE(m.mmactime,""), \'", "controllot":"\', COALESCE(m.controllot,""), \'", "controllevels":"\', COALESCE(m.controllevels,""), \'", "calibratorlot":"\', COALESCE(m.calibratorlot,""), \'", "actime":"\', COALESCE(m.actime,""), \'", "calibratorlevels":"\', COALESCE(m.calibratorlevels,""), \'", "doctorid":"\', COALESCE(m.doctorid,""), \'", "pcrrlot":"\', COALESCE(m.pcrrlot,""), \'", "pcrrexpiration":"\', COALESCE(m.pcrrexpiration,""), \'", "assaylot":"\', COALESCE(m.assaylot,""), \'", "qclot":"\', COALESCE(m.qclot,""),\'"}\')  detail from sh_nuklein n left join mrlabm2000detail md on md.labrecordid = n.sourceid left join mrlabm2000 m on m.id = md.labm2000id where n.ubchtunid = '.$ubchtunid.' and n.sourcekey = "mrlabrecord" and m.testtype = "HBV"
   union
   select n.id, n.date, "PCR HCV" test, CONCAT(\'{"HCV_RNA":"\', COALESCE(n.HCV_RNA,""),\'", "testtype":"\', COALESCE(m.testtype,""), \'", "runtime":"\', COALESCE(m.runtime,""), \'",  "platename":"\', COALESCE(m.platename,""), \'", "dwplatename":"\', COALESCE(m.dwplatename,""), \'", "serlot":"\', COALESCE(m.serlot,""), \'", "serexpiration":"\', COALESCE(m.serexpiration,""), \'", "sectime":"\', COALESCE(m.sectime,""), \'", "mmactime":"\', COALESCE(m.mmactime,""), \'", "controllot":"\', COALESCE(m.controllot,""), \'", "controllevels":"\', COALESCE(m.controllevels,""), \'", "calibratorlot":"\', COALESCE(m.calibratorlot,""), \'", "actime":"\', COALESCE(m.actime,""), \'", "calibratorlevels":"\', COALESCE(m.calibratorlevels,""), \'", "doctorid":"\', COALESCE(m.doctorid,""), \'", "pcrrlot":"\', COALESCE(m.pcrrlot,""), \'", "pcrrexpiration":"\', COALESCE(m.pcrrexpiration,""), \'", "assaylot":"\', COALESCE(m.assaylot,""), \'", "qclot":"\', COALESCE(m.qclot,""),\'"}\')  detail from sh_nuklein n left join mrlabm2000detail md on md.labrecordid = n.sourceid left join mrlabm2000 m on m.id = md.labm2000id where n.ubchtunid = '.$ubchtunid.' and n.sourcekey = "mrlabrecord" and m.testtype = "HCV"
   union
   select n.id, n.date, "PCR HDV" test, CONCAT(\'{"HDV_RNA":"\' , COALESCE(n.HDV_RNA,""),\'", "id":"\', COALESCE(m.id,""), \'",  "testtype":"\', COALESCE(m.testtype,""), \'", "runtime":"\', COALESCE(m.runtime,""), \'",  "platename":"\', COALESCE(m.platename,""), \'", "dwplatename":"\', COALESCE(m.dwplatename,""), \'", "serlot":"\', COALESCE(m.serlot,""), \'", "serexpiration":"\', COALESCE(m.serexpiration,""), \'", "sectime":"\', COALESCE(m.sectime,""), \'", "mmactime":"\', COALESCE(m.mmactime,""), \'", "controllot":"\', COALESCE(m.controllot,""), \'", "controllevels":"\', COALESCE(m.controllevels,""), \'", "calibratorlot":"\', COALESCE(m.calibratorlot,""), \'", "actime":"\', COALESCE(m.actime,""), \'", "calibratorlevels":"\', COALESCE(m.calibratorlevels,""), \'", "doctorid":"\', COALESCE(m.doctorid,""), \'", "pcrrlot":"\', COALESCE(m.pcrrlot,""), \'", "pcrrexpiration":"\', COALESCE(m.pcrrexpiration,""), \'", "assaylot":"\', COALESCE(m.assaylot,""), \'", "qclot":"\', COALESCE(m.qclot,""),\'"}\')  detail from sh_nuklein n left join mrlabm2000detail md on md.labrecordid = n.sourceid left join mrlabm2000 m on m.id = md.labm2000id where n.ubchtunid = '.$ubchtunid.' and n.sourcekey = "mrlabrecord" and m.testtype = "HDV"
   union
   select id, date, "VITD" test,  CONCAT(\'{"isResVitD":"\',isResVitD ,\'", "VitD":"\' , VitD ,\'"}\') detail from mrlabrecord where ubchtunid = '.$ubchtunid.'  and isResVitD = "Y" and isPaidVitD = "Y"
   union
   select id, date, "FER" test,  CONCAT(\'{"isResFER":"\',isResFER ,\'", "FER":"\' , FER ,\'"}\') detail from mrlabrecord where ubchtunid = '.$ubchtunid.'  and isResFER = "Y" and isPaidFER = "Y"
   union
   select id, date, "ANTI" test,  CONCAT(\'{"HBsAg":"\',HBsAg ,\'", "anti_HCV":"\' , anti_HCV , \'", "anti_HDV":"\' , anti_HDV,\'"}\') detail from sh_hepatittest where ubchtunid = '.$ubchtunid.'  and (sourcekey = "mrlabrecord" or sourcekey = "screen" ) and (HBsAg !="" or anti_HCV !="" or anti_HDV !="")
   union
   select id, date, "COAGULO" test,  CONCAT(\'{"PTSEC":"\',PTSEC ,\'", "PTINR":"\' , PTINR , \'", "Fibrinogen":"\' , Fibrinogen , \'",  "aPTT":"\' , aPTT,\'"}\') detail from sh_coagulogramm where ubchtunid = '.$ubchtunid.' and sourcekey = "mrlabrecord" 
   union
   select id, date, "OTHER" test,  CONCAT(\'{"title":"\',title ,\'", "result":"\' , result , \'", "unit":"\' , unit,\'"}\') detail from sh_other where ubchtunid = '.$ubchtunid.'
   union
   select mr.id, mr.date, "SYSMEX" test,
    CONCAT(\'{"isResHCV_AbQ":"\', COALESCE(mr.isResHCV_AbQ,""), \'",  "isResTSH":"\', COALESCE(mr.isResTSH,""), \'", "isResFT3":"\', COALESCE( mr.isResFT3,""), \'", "isResFT4":"\', COALESCE( mr.isResFT4,""), \'", "isResHBsAgQ":"\', COALESCE( mr.isResHBsAgQ,""), \'", "isResHBeAg":"\', COALESCE( mr.isResHBeAg,""), \'", "isResM2BPGI":"\', COALESCE( mr.isResM2BPGI,""), \'", "isResaHBs":"\', COALESCE(mr.isResaHBs,""), \'", "isResAFP":"\', COALESCE(mr.isResAFP,""), \'", "isResGenotype":"\', COALESCE( mr.isResGenotype,""), \'", "HCV_AbQ":"\', COALESCE(mr.HCV_AbQ,""), \'", "TSH":"\', COALESCE(mr.TSH,""), \'", "FT3":"\', COALESCE(mr.FT3,""), \'", "FT4":"\', COALESCE(mr.FT4,""), \'", "HBsAgQ":"\', COALESCE( mr.HBsAgQ,""), \'", "HBeAg":"\', COALESCE( mr.HBeAg,""), \'", "M2BPGI":"\', COALESCE( mr.M2BPGI ,""), \'", "aHBs":"\', COALESCE( mr.aHBs,""), \'", "AFP":"\', COALESCE( mr.AFP,""), \'", "Genotype":"\', COALESCE(mr.Genotype,""), \'",  "assaylot":"\', COALESCE(m.assaylot,""), \'",  "calibratorlot":"\', COALESCE(m.calibratorlot,""), \'",  "qclot":"\', COALESCE(m.qclot,""),\'"}\')  detail
    from mrlabrecord mr
    left join mrlabm2000detail md on md.labrecordid = mr.id
    left join mrlabm2000 m on m.id = md.labm2000id
    where mr.ubchtunid = '.$ubchtunid.' and (mr.isResHCV_AbQ = "Y" or mr.isResTSH = "Y" or mr.isResFT3 = "Y" or mr.isResFT4 = "Y" or mr.isResHBsAgQ = "Y" or mr.isResHBeAg = "Y" or mr.isResM2BPGI  = "Y" or mr.isResaHBs = "Y" or mr.isResAFP = "Y" or mr.isResGenotype)) AR order by AR.date DESC');

      if($result != NULL){
              $response["status"] = "success";
              $response["message"] = "";
              $response['totalcount'] = 0;
              $rows =  array();
              while($r =$result->fetch_assoc())
             {
              $rows[] = $r;
             }
             if(count($rows) > 0)
              {
                $response['totalcount'] = count($rows);
                $response['data'] = json_encode($rows);
              }
            else $response['data'] = [];
              echoResponse(200, $response);
      }else{
          $response["status"] = "error";
          $response["message"] = "Утга авахад алдаа гарлаа!";
          echoResponse(201, $response);
      }
});

//
//
//

$app->post('/oa_print', function() use ($app) {

    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['id'];

    $date = $r->date;
    $rd = $r->rd;
    $testtype = $r->testtype;
    if($testtype=="ANTI")
    { $result = $db->getOneRecord("SELECT ubchtunid, HBsAg, anti_HCV HCV_Ab, anti_HDV HDV_Ab, date from sh_hepatittest where regdate > '$date' and sourcekey = 'mrlabrecord' and ubchtunid = (select id from ubchtun_main where rd = '$rd' limit 1) order by regdate"); }
    else if($testtype=="HBV") {
      $result = $db->getOneRecord("SELECT sourceid from sh_nuklein where regdate > '$date' and HBV_DNA !='' and sourcekey = 'mrlabrecord' and ubchtunid = (select id from ubchtun_main where rd = '$rd' limit 1) order by regdate");
    }
    else if($testtype=="HCV") {
      $result = $db->getOneRecord("SELECT sourceid from sh_nuklein where regdate > '$date' and HCV_RNA !='' and sourcekey = 'mrlabrecord' and ubchtunid = (select id from ubchtun_main where rd = '$rd' limit 1) order by regdate");
    }
    else if($testtype=="HDV") {
      $result = $db->getOneRecord("SELECT sourceid from sh_nuklein where regdate > '$date' and HDV_RNA !='' and sourcekey = 'mrlabrecord' and ubchtunid = (select id from ubchtun_main where rd = '$rd' limit 1) order by regdate");
    }
    if($result)
      {
          $response["status"] = "success";
          $response["message"] = "";
          foreach($result as $column => $value)
                {
                    $response["data"][$column] = $value;
                }
      }
     else
      {
          $response["status"] = "error";
          $response["message"] = "Алдаа гарлаа.";
      }
      echoResponse(200, $response);
});

//
// saveU
//
$app->post('/saveU', function() use ($app) {

    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['id'];

    $column_names = array();
    $table_name =  $r->table;
    $id = $r->id;
    $data = $r->data;
    $systemcode = $r->systemcode;
    if(ctype_digit($systemcode)){
       $result = $db->getOneRecord("select id from ubchtun_main where upper(systemcode)=upper('$systemcode')");
     } else {
       $result = $db->getOneRecord("select id from ubchtun_main where upper(rd)=upper('$systemcode')");
     }
    if($result){
       $data->ubchtunid = $result["id"];
              $result = $db->getRecord("SELECT COLUMN_NAME
                                        FROM INFORMATION_SCHEMA.COLUMNS
                                        WHERE TABLE_SCHEMA = 'onomllc_hepatitis' AND TABLE_NAME = '$table_name'");
              if($result){
                    if($id == "-1")
                    {

                      //new row (insert)
                      $data->regdate = date("Y-m-d H:i:s");
                      $data->reguser = $userid;
                      $data->doctorid = $userid;
                     foreach($result as $column => $value)
                         {
                          if($value["COLUMN_NAME"] != "id")
                           $column_names[] = $value["COLUMN_NAME"];
                         }
                      $result = $db->insertIntoTable($data, $column_names, $table_name);
                       if ($result != NULL) {
                                        $response["status"] = "success";
                                        $response["message"] = "Амжилттай хадгаллаа.";
                                } else {
                                    $response["status"] = "error";
                                    $response["message"] = "Хадгалахад алдаа гарлаа.";
                                }
                    }
                    else
                    {
                      //update row
                        $data->id = $id;
                        $data->updated = date("Y-m-d H:i:s");
                        $data->updatedby = $userid;
                               foreach($result as $column => $value)
                                              {
                                                if($value["COLUMN_NAME"] != "id")
                                                $column_names[] = $value["COLUMN_NAME"];
                                              }
                                $where_clause = "where id = $id";

                                $result = $db->updateTable($data, $column_names, $table_name, $where_clause );
                                if ($result != NULL) {
                                        $response["status"] = "success";
                                        $response["message"] = "Амжилттай хадгаллаа.";
                                } else {
                                    $response["status"] = "error";
                                    $response["message"] = "Хадгалахад алдаа гарлаа.";
                                }
                    }

                }
                else
                {
                    $response["status"] = "error";
                    $response["message"] = "Хадгалахад алдаа гарлаа.";
                }
              }
     else
      {
          $response["status"] = "error";
          $response["message"] = "Хадгалахад алдаа гарлаа.";
      }
      echoResponse(200, $response);
});
//
// DeleteU
//
$app->post('/deleteU', function() use ($app) {

    $response = array();
    $r = json_decode($app->request->getBody());
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['id'];

    $table_name = $r->table;
    $id = $r->id;
    $result = $db->deleteRecord("DELETE FROM $table_name WHERE id = $id");
    if($result)
      {
          $response["status"] = "success";
          $response["message"] = "Амжилттай устгалаа.";
      }
     else
      {
          $response["status"] = "error";
          $response["message"] = "Устгахад алдаа гарлаа.";
      }
      echoResponse(200, $response);
});

//
// getConstant
//
$app->post('/getConstant', function() use ($app) {
  $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $rd = $session['rd'];
    $r = json_decode($app->request->getBody());
    $table = $r->tablename;
    $where = $r->where;
    $orderby = $r->orderby;
    $result = $db->getRecord("select * from $table ".$where." ".$orderby);
     if ($result != NULL) {
                $response["status"] = "success";
                $response["message"] = "";
                foreach($result as $column => $value)
                {
                    $response["data"][$column] = $value;
                }
                echoResponse(200, $response);
        } else {
                $response["status"] = "error";
                $response["message"] = "Хэрэглэгч бүртгэлгүй байна.".$systemcode;
                echoResponse(201, $response);
        }
});
//
// getPatientProfile
//
$app->post('/getPatientHT', function() use ($app) {
  $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $rd = $session['rd'];
    $r = json_decode($app->request->getBody());
    $systemcode = $r->systemcode;
     if(ctype_digit($systemcode))
    {
    $result = $db->getOneRecord("select ht.*
      from ubchtun_main u left join sh_hepatittest ht on ht.ubchtunid = u.id
      where upper(u.systemcode)=upper('$systemcode') and ht.sourcekey = 'screen'");
     }
    else {
     $result = $db->getOneRecord("select ht.*
      from ubchtun_main u left join sh_hepatittest ht on ht.ubchtunid = u.id
      where upper(u.rd)=upper('$systemcode') and ht.sourcekey = 'screen'");
     }
     if ($result != NULL) {
                $response["status"] = "success";
                $response["message"] = "";
                foreach($result as $column => $value)
                {
                    $response[$column] = $value;
                }
                echoResponse(200, $response);
        } else {
                $response["status"] = "error";
                $response["message"] = "Хэрэглэгч бүртгэлгүй байна.".$systemcode;
                echoResponse(201, $response);
        }
});
//
// savePatientHT
//
$app->post('/savePatientHT', function() use ($app) {

    $response = array();
    $r = json_decode($app->request->getBody());
    $user = $r->user;
    $db = new DbHandler();
    $session = $db->getSession();
    $systemcode = $user->systemcode;
    $result = $db->getOneRecord("select id from ubchtun_main where upper(systemcode)=upper('$systemcode')");
    if($result){
      $ubchtunid = $result["id"];
    $isUserExists = $db->getOneRecord("select 1 from sh_hepatittest where upper(ubchtunid)=upper('$ubchtunid') and sourcekey = 'screen'");
    if(!$isUserExists){
        $user->reguser = $session['id'];
        $user->regdate = date("Y-m-d H:i:s");
        $user->ubchtunid = $ubchtunid;
        $user->HBsAg = ($user->HBsAg == "1" ? "Y": ($user->HBsAg == "2" ? "N" : ""));
        $user->anti_HCV = ($user->anti_HCV == "1" ? "Y": ($user->anti_HCV == "2" ? "N" : ""));
        $user->sourcekey = "screen";
        $user->date = date("Y-m-d");
        $tabble_name = "sh_hepatittest";
        $column_names = array('ubchtunid', 'HBsAg', 'anti_HCV', 'reguser', 'regdate', 'sourcekey', 'date');
        $result = $db->insertIntoTable($user, $column_names, $tabble_name);
        if ($result != NULL) {
          $remobile = $db->getOneRecord("select mobile, tpass, systemcode from ubchtun_main where upper(id)=upper('$ubchtunid')");
          $mobile = $remobile["mobile"];
           $db->sendMSG($mobile, 'Shinjilgeenii hariu HBsAg:'.($user->HBsAg == "Y" ? "Positive(+)": ($user->HBsAg == "N" ? "Negative(-)" : "Invalid")).' anti_HCV: '.($user->anti_HCV == "Y" ? "Positive(+)": ($user->anti_HCV == "N" ? "Negative(-)" : "Invalid")).' burtgel.eleg.mn login:'.$remobile["systemcode"] .' pass:'.$remobile["tpass"], 'client11');
            $response["status"] = "success";
            $response["message"] = "Амжилттай хадгаллаа.";
        }
    }
    else{
        verifyRequiredParams(array('HBsAg', 'anti_HCV', 'systemcode'),$user);
        $user->sourcekey = "screen";
        $where_clause = "where upper(ubchtunid)=upper('$ubchtunid')";
        $user->HBsAg = ($user->HBsAg == "1" ? "Y": ($user->HBsAg == "2" ? "N" : ""));
        $user->anti_HCV = ($user->anti_HCV == "1" ? "Y": ($user->anti_HCV == "2" ? "N" : ""));
            $tabble_name = "sh_hepatittest";
            $column_names = array('HBsAg', 'anti_HCV');

            $result = $db->updateTable($user, $column_names, $tabble_name, $where_clause );
            if ($result != NULL) {
                    $response["status"] = "success";
                    $response["message"] = "Амжилттай хадгаллаа.";
            } else {
                $response["status"] = "error";
                $response["message"] = "хариу оруулахад алдаа гарлаа.";
            }
        }
      }
      else
      {
        $response["message"] = $result["id"];
      }
      echoResponse(200, $response);
});
//
// getPatientSh
//
$app->post('/getPatientSH', function() use ($app) {
  $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $rd = $session['rd'];
    $r = json_decode($app->request->getBody());
    $systemcode = $r->systemcode;
    $response["hepatittest"] = [];
    $response["biohimi"]=[];
    $response["blood"]=[];
    $response["sismex"]=[];
    $response["fibroscan"]=[];
    $response["nuklein"]=[];
    $response["shees"]=[];
    $response["autoimuni"]=[];
    $response["diabet"]=[];
    $response["coagulogramm"]=[];
    $response["visualdiagnosis"]=[];
    $response["other"]=[];
     if(ctype_digit($systemcode))
     {
          $resul_hepatittest = $db->getRecord("select * from sh_hepatittest where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
          $result_biohimi = $db->getRecord("select * from sh_biohimi where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
          $result_blood = $db->getRecord("select * from sh_blood where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");

          $result_sismex = $db->getRecord("select * from sh_sismeks where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
          $result_fibroscan = $db->getRecord("select * from sh_fibroscan where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
          $result_nuklein = $db->getRecord("select * from sh_nuklein where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
          $result_shees = $db->getRecord("select * from sh_shees where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
          $result_autoimuni = $db->getRecord("select * from sh_autoimuni where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
          $result_diabet = $db->getRecord("select * from sh_diabit where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
          $result_coagulogramm = $db->getRecord("select * from sh_coagulogramm where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
          $result_visualdiagnosis = $db->getRecord("select * from sh_visualdiagnosis where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
          $result_other = $db->getRecord("select * from sh_other where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");

      }
      else {
          $resul_hepatittest = $db->getRecord("select * from sh_hepatittest where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
          $result_biohimi = $db->getRecord("select * from sh_biohimi where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
          $result_blood = $db->getRecord("select * from sh_blood where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");

          $result_sismex = $db->getRecord("select * from sh_sismeks where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
          $result_fibroscan = $db->getRecord("select * from sh_fibroscan where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
          $result_nuklein = $db->getRecord("select * from sh_nuklein where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
          $result_shees = $db->getRecord("select * from sh_shees where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode'))");
           $result_autoimuni = $db->getRecord("select * from sh_autoimuni where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
          $result_diabet = $db->getRecord("select * from sh_diabit where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
          $result_coagulogramm = $db->getRecord("select * from sh_coagulogramm where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
          $result_visualdiagnosis = $db->getRecord("select * from sh_visualdiagnosis where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
          $result_other = $db->getRecord("select * from sh_other where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
      }
     if ($result_shees != NULL && $resul_hepatittest != NULL && $result_biohimi != NULL && $result_blood != NULL && $result_sismex != NULL && $result_fibroscan != NULL && $result_nuklein != NULL) {
                $response["status"] = "success";
                $response["message"] = "";
                foreach($resul_hepatittest as $column => $value)
                {
                    $response["hepatittest"][$column] = $value;
                }
                foreach($result_biohimi as $column => $value)
                {
                    $response["biohimi"][$column] = $value;
                }
                foreach($result_nuklein as $column => $value)
                {
                    $response["nuklein"][$column] = $value;
                }
                foreach($result_blood as $column => $value)
                {
                    $response["blood"][$column] = $value;
                }
                foreach($result_fibroscan as $column => $value)
                {
                    $response["fibroscan"][$column] = $value;
                }
                foreach($result_sismex as $column => $value)
                {
                    $response["sismex"][$column] = $value;
                }
                 foreach($result_shees as $column => $value)
                {
                    $response["shees"][$column] = $value;
                }
                 foreach($result_autoimuni as $column => $value)
                {
                    $response["autoimuni"][$column] = $value;
                }
                 foreach($result_diabet as $column => $value)
                {
                    $response["diabet"][$column] = $value;
                }
                 foreach($result_coagulogramm as $column => $value)
                {
                    $response["coagulogramm"][$column] = $value;
                }
                 foreach($result_visualdiagnosis as $column => $value)
                {
                    $response["visualdiagnosis"][$column] = $value;
                }
                foreach($result_other as $column => $value)
                {
                    $response["other"][$column] = $value;
                }
                echoResponse(200, $response);
        } else {
                $response["status"] = "error";
                $response["message"] = "Хэрэглэгч бүртгэлгүй байна.";
                echoResponse(201, $response);
        }
});

//
// getPreliminaryExam
//
$app->post('/getpedata', function() use ($app) {
  $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $r = json_decode($app->request->getBody());
    $id = $r->id;
    $resulpe = $db->getRecord("select m.*, round((m.weight * 10000)/(m.height*m.height)) bmi, u.systemcode from mrPreliminaryExam m inner join ubchtun_main u on u.id = m.ubchtunid where m.id = $id");
     if ($resulpe != NULL) {
                $response["status"] = "success";
                $response["message"] = "";
                foreach($resulpe as $column => $value)
                {
                    $response["data"][$column] = $value;
                }
                echoResponse(200, $response);
                
        } else {
                $response["status"] = "error";
                $response["message"] = "Хэрэглэгч бүртгэлгүй байна.";
                echoResponse(201, $response);
        }
});
//
// getPreliminaryExam
//
$app->post('/getPE_data', function() use ($app) {
  $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $rd = $session['rd'];
    $r = json_decode($app->request->getBody());
    $systemcode = $r->systemcode;
    $response["pe_data"]=[];
     if(ctype_digit($systemcode))
     {
          $resulpe = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from mrPreliminaryExam m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
      }
      else {
         $resulpe = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from mrPreliminaryExam m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
      }
     if ($resulpe != NULL) {
                $response["status"] = "success";
                $response["message"] = "";
                foreach($resulpe as $column => $value)
                {
                    $response["pe_data"][$column] = $value;
                }
                echoResponse(200, $response);
        } else {
                $response["status"] = "error";
                $response["message"] = "Хэрэглэгч бүртгэлгүй байна.";
                echoResponse(201, $response);
        }
});
//
// getPatientExam
//
$app->post('/getPatientExam', function() use ($app) {
  $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $rd = $session['rd'];
    $r = json_decode($app->request->getBody());
    $systemcode = $r->systemcode;
    $response["exam"]=[];
     if(ctype_digit($systemcode))
     {
          $resulexam = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from mrPatientExam m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
      }
      else {
         $resulexam = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from mrPatientExam m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
      }
     if ($resulexam != NULL) {
                $response["status"] = "success";
                $response["message"] = "";
                foreach($resulexam as $column => $value)
                {
                    $response["exam"][$column] = $value;
                }
                echoResponse(200, $response);
        } else {
                $response["status"] = "error";
                $response["message"] = "Хэрэглэгч бүртгэлгүй байна.";
                echoResponse(201, $response);
        }
});

//
// getPatientPain
//
$app->post('/getPatientPain', function() use ($app) {
    $response = array();
      $db = new DbHandler();
      $session = $db->getSession();
      $rd = $session['rd'];
      $r = json_decode($app->request->getBody());
      $systemcode = $r->systemcode;
      $response["pain"]=[];
       if(ctype_digit($systemcode))
       {
            $resulpain = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from mrPatientPain m where ubchtunid = (select u.id
            from ubchtun_main u
            where upper(u.systemcode)=upper('$systemcode')) order by date desc");
        }
        else {
           $resulpain = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from mrPatientPain m where ubchtunid = (select u.id
            from ubchtun_main u
            where upper(u.rd)=upper('$systemcode')) order by date desc");
        }
       if ($resulpain != NULL) {
                  $response["status"] = "success";
                  $response["message"] = "";
                  foreach($resulpain as $column => $value)
                  {
                      $response["pain"][$column] = $value;
                  }
                  echoResponse(200, $response);
          } else {
                  $response["status"] = "error";
                  $response["message"] = "Хэрэглэгч бүртгэлгүй байна.";
                  echoResponse(201, $response);
          }
  });
//
// getPatientHist
//
$app->post('/getPatientHist', function() use ($app) {
  $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $rd = $session['rd'];
    $r = json_decode($app->request->getBody());
    $systemcode = $r->systemcode;
    $response["hist"]=[];
    $response["surgery"]=[];
    $response["alergy"]=[];
     if(ctype_digit($systemcode))
     {
          $resulthist = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from em_ubchniituuh m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
          $resultsurgery = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from em_meszasal m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
          $resultalergy = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from em_harshildagem m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
      }
      else {
         $resulthist = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from em_ubchniituuh m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
          $resultsurgery = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from em_meszasal m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
          $resultalergy = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from em_harshildagem m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
      }
     if ($resulthist != NULL && $resultsurgery != NULL && $resultalergy != NULL) {
                $response["status"] = "success";
                $response["message"] = "";
                foreach($resulthist as $column => $value)
                {
                    $response["hist"][$column] = $value;
                }
                foreach($resultsurgery as $column => $value)
                {
                    $response["surgery"][$column] = $value;
                }
                foreach($resultalergy as $column => $value)
                {
                    $response["alergy"][$column] = $value;
                }
                echoResponse(200, $response);
        } else {
                $response["status"] = "error";
                $response["message"] = "Хэрэглэгч бүртгэлгүй байна.";
                echoResponse(201, $response);
        }
});
//
// getPatientTreatment
//
$app->post('/getPatientTreat', function() use ($app) {
  $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $rd = $session['rd'];
    $r = json_decode($app->request->getBody());
    $systemcode = $r->systemcode;
    $response["treatment"]=[];
    $response["prescription"]=[];
    $response["diagnosis"]=[];
    $response["drug"]=[];
    $response["drugsideeffect"]=[];
    $response["meldscore"]=[];
    $response["childpugh"]=[];
     if(ctype_digit($systemcode))
     {
          $result_treatment = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from mrtreatment m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
          $result_diagnosis = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from mrdiagnosis m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
          $result_prescription = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from mrprescription m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
          $result_drug = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from mrdrugpatient m where rd = (select u.rd
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
          $result_drugsideeffect = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from mrdrugsideeffect m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
          $result_meldscore = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from meldscore m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
          $result_childpugh = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from childpugh m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')) order by date desc");
      }
      else {
         $result_treatment = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from mrtreatment m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
          $result_diagnosis = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from mrdiagnosis m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
          $result_prescription = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from mrprescription m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
          $result_drug = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from mrdrugpatient m where upper(rd) = upper('$systemcode') order by date desc");
          $result_drugsideeffect = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from mrdrugsideeffect m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
          $result_meldscore = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from meldscore m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
          $result_childpugh = $db->getRecord("select *, (select userid from doctors_main where m.reguser = id) as sreguser from childpugh m where ubchtunid = (select u.id
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')) order by date desc");
      }
     if ($result_treatment != NULL && $result_diagnosis != NULL && $result_prescription != NULL && $result_drug != NULL) {
                $response["status"] = "success";
                $response["message"] = "";
                foreach($result_drugsideeffect as $column => $value)
                {
                    $response["drugsideeffect"][$column] = $value;
                }
                foreach($result_treatment as $column => $value)
                {
                    $response["treatment"][$column] = $value;
                }
                foreach($result_diagnosis as $column => $value)
                {
                    $response["diagnosis"][$column] = $value;
                }
                foreach($result_prescription as $column => $value)
                {
                    $response["prescription"][$column] = $value;
                }
                foreach($result_drug as $column => $value)
                {
                    $response["drug"][$column] = $value;
                }
                 foreach($result_childpugh as $column => $value)
                {
                    $response["childpugh"][$column] = $value;
                }
                 foreach($result_meldscore as $column => $value)
                {
                    $response["meldscore"][$column] = $value;
                }
                echoResponse(200, $response);
        } else {
                $response["status"] = "error";
                $response["message"] = "Хэрэглэгч бүртгэлгүй байна.";
                echoResponse(201, $response);
        }
});
//
// getPatientProfilew
//
$app->post('/getPatientProfilew', function() use ($app) {
  $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $rd = $session['rd'];
    $id = $session['id'];
    $r = json_decode($app->request->getBody());
    $systemcode = $r->systemcode;
     if(ctype_digit($systemcode))
     {
          $result = $db->getOneRecord("select TIMESTAMPDIFF(YEAR, u.birthday, CURDATE()) age, u.*
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode') and (u.reguser = $id or u.reguser = 0)");
      }
      else {
         $result = $db->getOneRecord("select TIMESTAMPDIFF(YEAR, u.birthday, CURDATE()) age, u.*
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode') and (u.reguser = $id or u.reguser = 0)");
      }
     if ($result != NULL) {
                $response["status"] = "success";
                $response["message"] = "";
                foreach($result as $column => $value)
                {
                    $response[$column] = $value;
                }
                echoResponse(200, $response);
        } else {
                $response["status"] = "error";
                $response["message"] = "Хэрэглэгч бүртгэлгүй байна.";
                echoResponse(201, $response);
        }
});

//
// getPatientProfile
//
$app->post('/getPatientProfile', function() use ($app) {
  $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $rd = $session['rd'];
    $r = json_decode($app->request->getBody());
    $systemcode = $r->systemcode;
     if(ctype_digit($systemcode))
     {
          $result = $db->getOneRecord("select TIMESTAMPDIFF(YEAR, u.birthday, CURDATE()) age, u.*
          from ubchtun_main u
          where upper(u.systemcode)=upper('$systemcode')");
      }
      else {
         $result = $db->getOneRecord("select TIMESTAMPDIFF(YEAR, u.birthday, CURDATE()) age, u.*
          from ubchtun_main u
          where upper(u.rd)=upper('$systemcode')");
      }
     if ($result != NULL) {
                $response["status"] = "success";
                $response["message"] = "";
                foreach($result as $column => $value)
                {
                    $response[$column] = $value;
                }
                echoResponse(200, $response);
        } else {
                $response["status"] = "error";
                $response["message"] = "Хэрэглэгч бүртгэлгүй байна.";
                echoResponse(201, $response);
        }
});

//
// savePatientProfile
//
$app->post('/savePatientProfile', function() use ($app) {

    $response = array();
    $r = json_decode($app->request->getBody());
    $user = $r->user;
    $db = new DbHandler();
    $session = $db->getSession();
    $reguser = $session["id"];
    if($user->mode == "edit"){
        verifyRequiredParams(array('firstname', 'lastname', 'mobile','rd'),$user); //email

        $systemcode= $user->systemcode;
        if(ctype_digit($systemcode)) $where_clause = "upper(systemcode)=upper('$systemcode')";
        else $where_clause = "upper(rd)=upper('$systemcode')";

            $tabble_name = "ubchtun_main";
            $column_names = array('firstname', 'lastname', 'email', 'mobile','phone','cityid','districtid','khorooid','address', '',
                'description','hynagch','reguser','regdate','active','occupation','education', 'employment', 'home_type', 'family_size', 'monthly_income', 'birthday',
                'marrige_status','driver_license','contact_relationship','ethnicity','emd');

            $result = $db->updateTable($user, $column_names, $tabble_name, $where_clause );
            if ($result != NULL) {
                    $response["status"] = "success";
                    $response["message"] = "Амжилттай хадгаллаа.";
                    echoResponse(200, $response);
            } else {
                $response["status"] = "error";
                $response["message"] = "Шинэ хэрэглэгч үүсгэхэд алдаа гарлаа.";
                echoResponse(201, $response);
            }
        } else {
    require_once 'passwordHash.php';
    $passStr = passwordHash::generateRandomString();
    verifyRequiredParams(array('firstname', 'lastname', 'rd', 'mobile'),$user);
    $rd = $user->rd;
    $email = $user->email;
    $mobile = $user->mobile;

    $isUserExists = $db->getOneRecord("select 1 from ubchtun_main where upper(rd)=upper('$rd')");
    if(!$isUserExists){
        $user->password = passwordHash::hash($passStr);
        $user->reguser = $reguser;
        $user->regdate = date("Y-m-d H:i:s");
        $user->tpass = $passStr;
        $tabble_name = "ubchtun_main";
        $column_names = array('firstname', 'lastname', 'email', 'rd', 'password','tpass','mobile','phone','cityid','districtid','khorooid','address','description','hynagch','reguser','regdate','occupation','education', 'employment', 'home_type', 'family_size', 'monthly_income','marrige_status','driver_license','contact_relationship','ethnicity', 'emd');
        $result = $db->insertIntoTable($user, $column_names, $tabble_name);
        if ($result != NULL) {
             $pid = $result;
             $response["ubchtunid"] = $result;
             $result = $db->updateQuery("update ubchtun_main set systemcode = (select lpad(max(u1.systemcode) + 1,8,0) from (select systemcode from ubchtun_main) u1),
                                            birthday = (select case when substr(u2.rd,5,2) > '12' then concat('20', substr(u2.rd,3,2),'-',(substr(u2.rd,5,1)-2),substr(u2.rd,6,1),'-',substr(u2.rd,7,2)) else  concat('19' , substr(u2.rd,3,2),'-',substr(u2.rd,5,2),'-',substr(u2.rd,7,2)) end from (select id,rd from ubchtun_main) u2 where u2.id = $pid),
                                            gender = (select case when (substr(u3.rd,9,1) % 2) = 0 then 'ЭМ' else  'ЭР' end from (select id,rd from ubchtun_main) u3 where u3.id = $pid)
                                            where id = $pid");
                if ($result != NULL) {
                $response["status"] = "success";
                $response["message"] = "Хэрэглэгч амжилттай үүсгэлээ.";

                $result = $db->getOneRecord("select systemcode from ubchtun_main where id = $pid");
                if ($result != NULL) {
                      //   $db->sendMSG($mobile, 'Eruul eleg: URL: burtgel.eleg.mn SystemCode:'.$result['systemcode'].' password: '.$passStr.' Ta systemd nevterch delgerengui medeelel oruulna uu', 'client11');
                         $response["systemcode"] = $result['systemcode'];
                                      }
                echoResponse(200, $response);
              }
        } else {
            $response["status"] = "error";
            $response["message"] = "Шинэ хэрэглэгч үүсгэхэд алдаа гарлаа.";
            echoResponse(201, $response);
        }
    }else{
        $response["status"] = "error";
        $response["message"] = "Тухайн регистрийн дугаартай хэрэглэгч үүссэн байна!";
        echoResponse(201, $response);
    }
  }

});

//
//getReportFinance
//
$app->post('/getReportFinance', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $date = $r->date;
   // $date = "2015-05-28";
    $result = $db->getRecord("SELECT @rownum:=@rownum + 1 AS num, ma.* from
(
select
  u.firstname, u.lastname ,
  IF(m.isPaidDoctor = 'Y', IF(isSecondDoctor = 'Y', 10000, 20000), 0) as PaidDoctor,
  IF(m.isPaidDoctor = 'Y', d.userid, '') as WhichDoctor,
  IF(m.isPaidHBsAg = 'Y', 3000, 0) as PaidHBsAg,
  IF(m.isPaidHCV_Ab = 'Y', 3000, 0) as PaidHCV_Ab,
  IF(m.isPaidHIV = 'Y', 5000, 0) as PaidHIV,
  IF(m.isPaidSyphilis = 'Y', 5000, 0) as PaidSyphilis,
  IF(m.isPaidHDV_Ab = 'Y', 10000, 0) as PaidHDV_Ab,
  IF(m.isPaidHBV_DNA = 'Y', 120000, 0) as PaidHBV_DNA,
  IF(m.isPaidHCV_DNA = 'Y', IF(m.isPaidEMD = 'Y', 0, 90000), 0) as PaidHCV_DNA,
  IF(m.isPaidHDV_DNA = 'Y', 120000, 0) as PaidHDV_DNA,
  IF(m.isPaidHBsAgQ = 'Y', 25000, 0) as PaidHBsAgQ,

  IF(m.isPaidHCV_AbQ = 'Y', 20000, 0) as PaidHCV_AbQ,
  IF(m.isPaidTSH = 'Y', 15500, 0) as PaidTSH,
  IF(m.isPaidFT3 = 'Y', 15500, 0) as PaidFT3,
  IF(m.isPaidFT4 = 'Y', 15500, 0) as PaidFT4,


  IF(m.isPaidHBeAg = 'Y', 36000, 0) as PaidHBeAg,
  IF(m.isPaidaHBs = 'Y', 20000, 0) as PaidaHBs,
  IF(m.isPaidM2BPGI = 'Y', 65000, 0) as PaidM2BPGI,
  IF(m.isPaidAFP = 'Y', 25000, 0) as PaidAFP,
  IF(m.isPaidFibroScan = 'Y', 60000, 0) as PaidFibroScan,
  IF(m.isPaidMDTConsultation = 'Y', 40000, 0) as PaidMDTConsultation,
  IF(m.isPaidGenotype = 'Y', 120000, 0) as PaidGenotype,
  IF(m.isPaidVitD = 'Y', 15000, 0) as PaidVitD,
  IF(m.isPaidFER = 'Y', 15000, 0) as PaidFER,
  IF(m.isPaidCoagulo = 'Y', 15000, 0) as PaidCoagulo,
  IF(m.isPaidBlood = 'Y', 13500, 0) as PaidBlood,
  IF(m.isPaidBiohimi = 'Y', m.priceBiohimi, 0) as PaidBiohimi,

  IF(m.isCash = 'Y', m.cashamount, 0) as cashpayment,
  IF(m.isCart = 'Y', m.cartamount, 0) as cartpayment,
  IF(m.isdiscount = 'Y', m.discountamount, 0) as discount,
  IF(m.isPaidEMD = 'Y', 1, 0) as EMD,
    IF(m.isresearchdisc = 'Y', m.researchdiscamount, 0) as researchdiscount,
    IF(m.isothercost = 'Y', m.othercostamount, 0) as othercost,

    m.remainPayment remain,
    (IF(m.isPaidDoctor = 'Y', IF(isSecondDoctor = 'Y', 10000, 20000), 0) + IF(m.isPaidHBsAg = 'Y', 3000, 0) + IF(m.isPaidHCV_Ab = 'Y', 3000, 0) + IF(m.isPaidHIV = 'Y', 5000, 0) + IF(m.isPaidSyphilis = 'Y', 5000, 0) + IF(m.isPaidHDV_Ab = 'Y', 10000, 0)
    + IF(m.isPaidHBV_DNA = 'Y', 120000, 0) + IF(m.isPaidHCV_DNA = 'Y', IF(m.isPaidEMD = 'Y', 0, 90000), 0) + IF(m.isPaidHDV_DNA = 'Y', 120000, 0) + IF(m.isPaidHBsAgQ = 'Y', 25000, 0) + IF(m.isPaidFT4 = 'Y', 15500, 0)
    + IF(m.isPaidHBeAg = 'Y', 36000, 0) + IF(m.isPaidaHBs = 'Y', 20000, 0) + IF(m.isPaidM2BPGI = 'Y', 65000, 0) + IF(m.isPaidAFP = 'Y', 25000, 0) + IF(m.isPaidHCV_AbQ = 'Y', 20000, 0) + IF(m.isPaidTSH = 'Y', 15500, 0) + IF(m.isPaidFT3 = 'Y', 15500, 0)
    + IF(m.isPaidFibroScan = 'Y', 60000, 0) + IF(m.isPaidMDTConsultation = 'Y', 40000, 0) + IF(m.isPaidGenotype = 'Y', 120000, 0) + IF(m.isPaidVitD = 'Y', 15000, 0) + IF(m.isPaidFER = 'Y', 15000, 0) + IF(m.isPaidBlood = 'Y', 13500, 0) + IF(m.isPaidCoagulo = 'Y', 15000, 0) + IF(m.isPaidBiohimi = 'Y', m.priceBiohimi, 0)+ IF(m.isothercost = 'Y', m.othercostamount, 0))
    - IF(m.isCash = 'Y', m.cashamount, 0) -  IF(m.isCart = 'Y', m.cartamount, 0) - m.remainPayment - IF(m.isdiscount = 'Y', m.discountamount, 0)
    - IF(m.isresearchdisc = 'Y', m.researchdiscamount, 0) as gap,
     (IF(m.isCash = 'Y', m.cashamount, 0) + IF(m.isCart = 'Y', m.cartamount, 0)) as payment

from mrlabrecord m left join ubchtun_main u on u.id = m.ubchtunid left join doctors_main d on d.id = m.doctor where m.date = '$date'

) ma, (SELECT @rownum:=0) r

union all

SELECT '' as num, '' as lastname, 'Нийт дүн' as fisrtname, sum(ma.PaidDoctor) as PaidDoctor, '' as WhichDoctor, sum(ma.PaidHBsAg) PaidHBsAg, sum(ma.PaidHCV_Ab) PaidHCV_Ab, sum(ma.PaidHIV) PaidHIV, sum(ma.PaidSyphilis) PaidSyphilis, sum(ma.PaidHDV_Ab) PaidHDV_Ab, sum(ma.PaidHBV_DNA) PaidHBV_DNA, sum(ma.PaidHCV_DNA) PaidHCV_DNA, sum(ma.PaidHDV_DNA) PaidHDV_DNA, sum(ma.PaidHBsAgQ) PaidHBsAgQ, sum(ma.PaidHCV_AbQ) PaidHCV_AbQ, sum(ma.PaidTSH) PaidTSH, sum(ma.PaidFT3) PaidFT3, sum(ma.PaidFT4) PaidFT4, sum(ma.PaidHBeAg) PaidHBeAg, sum(ma.PaidaHBs) PaidaHBs, sum(ma.PaidM2BPGI) PaidM2BPGI, sum(ma.PaidAFP) PaidAFP, sum(ma.PaidFibroScan) PaidFibroScan, sum(ma.PaidMDTConsultation) PaidMDTConsultation, sum(ma.PaidGenotype) PaidGenotype, sum(ma.PaidVitD) PaidVitD, sum(ma.PaidFER) PaidFER,  sum(ma.PaidCoagulo) PaidCoagulo, sum(ma.PaidBlood) PaidBlood, sum(ma.PaidBiohimi) PaidBiohimi,sum(ma.cashpayment) cashpayment, sum(ma.cartpayment) cartpayment, sum(ma.discount) discount, sum(ma.EMD) EMD, sum(ma.researchdiscount) researchdiscount, sum(ma.othercost) othercost, sum(ma.remain) remain, sum(ma.gap) gap, sum(ma.payment) payment   from
(
select

  IF(m.isPaidDoctor = 'Y', IF(isSecondDoctor = 'Y', 10000, 20000), 0) as PaidDoctor,
  IF(m.isPaidDoctor = 'Y', d.userid, '') as WhichDoctor,
  IF(m.isPaidHBsAg = 'Y', 3000, 0) as PaidHBsAg,
  IF(m.isPaidHCV_Ab = 'Y', 3000, 0) as PaidHCV_Ab,
  IF(m.isPaidHIV = 'Y', 5000, 0) as PaidHIV,
  IF(m.isPaidSyphilis = 'Y', 5000, 0) as PaidSyphilis,
  IF(m.isPaidHDV_Ab = 'Y', 10000, 0) as PaidHDV_Ab,
  IF(m.isPaidHBV_DNA = 'Y', 120000, 0) as PaidHBV_DNA,
  IF(m.isPaidHCV_DNA = 'Y', IF(m.isPaidEMD = 'Y', 0, 90000), 0) as PaidHCV_DNA,
  IF(m.isPaidHDV_DNA = 'Y', 120000, 0) as PaidHDV_DNA,
  IF(m.isPaidHBsAgQ = 'Y', 25000, 0) as PaidHBsAgQ,

  IF(m.isPaidHCV_AbQ = 'Y', 20000, 0) as PaidHCV_AbQ,
  IF(m.isPaidTSH = 'Y', 15500, 0) as PaidTSH,
  IF(m.isPaidFT3 = 'Y', 15500, 0) as PaidFT3,
  IF(m.isPaidFT4 = 'Y', 15500, 0) as PaidFT4,

  IF(m.isPaidHBeAg = 'Y', 36000, 0) as PaidHBeAg,
  IF(m.isPaidaHBs = 'Y', 20000, 0) as PaidaHBs,
  IF(m.isPaidM2BPGI = 'Y', 65000, 0) as PaidM2BPGI,
  IF(m.isPaidAFP = 'Y', 25000, 0) as PaidAFP,
  IF(m.isPaidFibroScan = 'Y', 60000, 0) as PaidFibroScan,
  IF(m.isPaidMDTConsultation = 'Y', 40000, 0) as PaidMDTConsultation,
  IF(m.isPaidGenotype = 'Y', 120000, 0) as PaidGenotype,
  IF(m.isPaidVitD = 'Y', 15000, 0) as PaidVitD,
  IF(m.isPaidFER = 'Y', 15000, 0) as PaidFER,
  IF(m.isPaidCoagulo = 'Y', 15000, 0) as PaidCoagulo,
  IF(m.isPaidBlood = 'Y', 13500, 0) as PaidBlood,
  IF(m.isPaidBiohimi = 'Y', m.priceBiohimi, 0) as PaidBiohimi,

  IF(m.isCash = 'Y', m.cashamount, 0) as cashpayment,
  IF(m.isCart = 'Y', m.cartamount, 0) as cartpayment,
  IF(m.isdiscount = 'Y', m.discountamount, 0) as discount,
  IF(m.isPaidEMD = 'Y', 1, 0) as EMD,
    IF(m.isresearchdisc = 'Y', m.researchdiscamount, 0) as researchdiscount,
    IF(m.isothercost = 'Y', m.othercostamount, 0) as othercost,

    m.remainPayment remain,
    (IF(m.isPaidDoctor = 'Y', IF(isSecondDoctor = 'Y', 10000, 20000), 0) + IF(m.isPaidHBsAg = 'Y', 3000, 0) + IF(m.isPaidHCV_Ab = 'Y', 3000, 0) + IF(m.isPaidHIV = 'Y', 5000, 0) + IF(m.isPaidSyphilis = 'Y', 5000, 0) + IF(m.isPaidHDV_Ab = 'Y', 10000, 0)
    + IF(m.isPaidHBV_DNA = 'Y', 120000, 0) + IF(m.isPaidHCV_DNA = 'Y', IF(m.isPaidEMD = 'Y', 0, 90000), 0) + IF(m.isPaidHDV_DNA = 'Y', 120000, 0) + IF(m.isPaidHBsAgQ = 'Y', 25000, 0) + IF(m.isPaidFT4 = 'Y', 15500, 0)
    + IF(m.isPaidHBeAg = 'Y', 36000, 0) + IF(m.isPaidaHBs = 'Y', 20000, 0) + IF(m.isPaidM2BPGI = 'Y', 65000, 0) + IF(m.isPaidAFP = 'Y', 25000, 0) + IF(m.isPaidHCV_AbQ = 'Y', 20000, 0) + IF(m.isPaidTSH = 'Y', 15500, 0) + IF(m.isPaidFT3 = 'Y', 15500, 0)
    + IF(m.isPaidFibroScan = 'Y', 60000, 0) + IF(m.isPaidMDTConsultation = 'Y', 40000, 0) + IF(m.isPaidGenotype = 'Y', 120000, 0) +  IF(m.isPaidVitD = 'Y', 15000, 0) +  IF(m.isPaidFER = 'Y', 15000, 0) +  IF(m.isPaidCoagulo = 'Y', 15000, 0) +  IF(m.isPaidBlood = 'Y', 13500, 0)  + IF(m.isPaidBiohimi = 'Y', m.priceBiohimi, 0)+ IF(m.isothercost = 'Y', m.othercostamount, 0))
    - IF(m.isCash = 'Y', m.cashamount, 0) -  IF(m.isCart = 'Y', m.cartamount, 0) - m.remainPayment - IF(m.isdiscount = 'Y', m.discountamount, 0)
    - IF(m.isresearchdisc = 'Y', m.researchdiscamount, 0) as gap,
     (IF(m.isCash = 'Y', m.cashamount, 0) + IF(m.isCart = 'Y', m.cartamount, 0)) as payment

from mrlabrecord m left join doctors_main d on d.id = m.doctor where m.date = '$date'

) ma

union all


SELECT '' as num, '' as lastname, 'Нийт тоо' as fisrtname, sum(if(ma.PaidDoctor!=0,1,0)) as PaidDoctor, '' as WhichDoctor, sum(if(ma.PaidHBsAg!=0,1,0)) PaidHBsAg, sum(if(ma.PaidHCV_Ab!=0,1,0)) PaidHCV_Ab, sum(if(ma.PaidHIV!=0,1,0)) PaidHIV, sum(if(ma.PaidSyphilis!=0,1,0)) PaidSyphilis, sum(if(ma.PaidHDV_Ab!=0,1,0)) PaidHDV_Ab, sum(if(ma.PaidHBV_DNA!=0,1,0)) PaidHBV_DNA, sum(if(ma.PaidHCV_DNA!=0,1,0)) PaidHCV_DNA, sum(if(ma.PaidHDV_DNA!=0,1,0)) PaidHDV_DNA, sum(if(ma.PaidHBsAgQ!=0,1,0)) PaidHBsAgQ, sum(if(ma.PaidHCV_AbQ!=0,1,0)) PaidHCV_AbQ, sum(if(ma.PaidTSH!=0,1,0)) PaidTSH, sum(if(ma.PaidFT3!=0,1,0)) PaidFT3, sum(if(ma.PaidFT4!=0,1,0)) PaidFT4, sum(if(ma.PaidHBeAg!=0,1,0)) PaidHBeAg, sum(if(ma.PaidaHBs!=0,1,0)) PaidaHBs, sum(if(ma.PaidM2BPGI!=0,1,0)) PaidM2BPGI, sum(if(ma.PaidAFP!=0,1,0)) PaidAFP, sum(if(ma.PaidFibroScan!=0,1,0)) PaidFibroScan, sum(if(ma.PaidMDTConsultation!=0,1,0)) PaidMDTConsultation, sum(if(ma.PaidGenotype!=0,1,0)) PaidGenotype, sum(if(ma.PaidVitD!=0,1,0)) PaidVitD, sum(if(ma.PaidFER!=0,1,0)) PaidFER, sum(if(ma.PaidCoagulo!=0,1,0)) PaidCoagulo,sum(if(ma.PaidBlood!=0,1,0)) PaidBlood,  sum(if(ma.PaidBiohimi!=0,1,0)) PaidBiohimi, sum(if(ma.cashpayment!=0,1,0)) cashpayment, sum(if(ma.cartpayment!=0,1,0)) cartpayment, sum(if(ma.discount!=0,1,0)) discount, sum(if(ma.EMD!=0,1,0)) EMD, sum(if(ma.researchdiscount!=0,1,0)) researchdiscount, sum(if(ma.othercost!=0,1,0)) othercost, sum(if(ma.remain!=0,1,0)) remain, sum(if(ma.gap!=0,1,0)) gap, sum(if(ma.payment!=0,1,0)) payment  from
(
select

  IF(m.isPaidDoctor = 'Y', IF(isSecondDoctor = 'Y', 10000, 20000), 0) as PaidDoctor,
  IF(m.isPaidDoctor = 'Y', d.userid, '') as WhichDoctor,
  IF(m.isPaidHBsAg = 'Y', 3000, 0) as PaidHBsAg,
  IF(m.isPaidHCV_Ab = 'Y', 3000, 0) as PaidHCV_Ab,
  IF(m.isPaidHIV = 'Y', 5000, 0) as PaidHIV,
  IF(m.isPaidSyphilis = 'Y', 5000, 0) as PaidSyphilis,
  IF(m.isPaidHDV_Ab = 'Y', 10000, 0) as PaidHDV_Ab,
  IF(m.isPaidHBV_DNA = 'Y', 120000, 0) as PaidHBV_DNA,
  IF(m.isPaidHCV_DNA = 'Y', 90000, 0) as PaidHCV_DNA,
  IF(m.isPaidHDV_DNA = 'Y', 120000, 0) as PaidHDV_DNA,
  IF(m.isPaidHBsAgQ = 'Y', 25000, 0) as PaidHBsAgQ,

  IF(m.isPaidHCV_AbQ = 'Y', 20000, 0) as PaidHCV_AbQ,
  IF(m.isPaidTSH = 'Y', 15500, 0) as PaidTSH,
  IF(m.isPaidFT3 = 'Y', 15500, 0) as PaidFT3,
  IF(m.isPaidFT4 = 'Y', 15500, 0) as PaidFT4,


  IF(m.isPaidHBeAg = 'Y', 36000, 0) as PaidHBeAg,
  IF(m.isPaidaHBs = 'Y', 20000, 0) as PaidaHBs,
  IF(m.isPaidM2BPGI = 'Y', 65000, 0) as PaidM2BPGI,
  IF(m.isPaidAFP = 'Y', 25000, 0) as PaidAFP,
  IF(m.isPaidFibroScan = 'Y', 60000, 0) as PaidFibroScan,
  IF(m.isPaidMDTConsultation = 'Y', 40000, 0) as PaidMDTConsultation,
  IF(m.isPaidGenotype = 'Y', 120000, 0) as PaidGenotype,
  IF(m.isPaidVitD = 'Y', 15000, 0) as PaidVitD,
  IF(m.isPaidFER = 'Y', 15000, 0) as PaidFER,
  IF(m.isPaidCoagulo = 'Y', 15000, 0) as PaidCoagulo,
  IF(m.isPaidBlood = 'Y', 13500, 0) as PaidBlood,
  IF(m.isPaidBiohimi = 'Y', m.priceBiohimi, 0) as PaidBiohimi,

  IF(m.isCash = 'Y', m.cashamount, 0) as cashpayment,
  IF(m.isCart = 'Y', m.cartamount, 0) as cartpayment,
  IF(m.isdiscount = 'Y', m.discountamount, 0) as discount,
  IF(m.isPaidEMD = 'Y', 1, 0) as EMD,
  IF(m.isresearchdisc = 'Y', m.researchdiscamount, 0) as researchdiscount,
  IF(m.isothercost = 'Y', m.othercostamount, 0) as othercost,

    m.remainPayment remain,
     (IF(m.isPaidDoctor = 'Y', IF(isSecondDoctor = 'Y', 10000, 20000), 0) + IF(m.isPaidHBsAg = 'Y', 3000, 0) + IF(m.isPaidHCV_Ab = 'Y', 3000, 0)  + IF(m.isPaidHIV = 'Y', 5000, 0) + IF(m.isPaidSyphilis = 'Y', 5000, 0) + IF(m.isPaidHDV_Ab = 'Y', 10000, 0)
    + IF(m.isPaidHBV_DNA = 'Y', 120000, 0) + IF(m.isPaidHCV_DNA = 'Y',IF(m.isPaidEMD = 'Y', 0, 90000), 0) + IF(m.isPaidHDV_DNA = 'Y', 120000, 0) + IF(m.isPaidHBsAgQ = 'Y', 25000, 0)  + IF(m.isPaidFT4 = 'Y', 15500, 0)
    + IF(m.isPaidHBeAg = 'Y', 36000, 0) + IF(m.isPaidaHBs = 'Y', 20000, 0) + IF(m.isPaidM2BPGI = 'Y', 65000, 0) + IF(m.isPaidAFP = 'Y', 25000, 0) + IF(m.isPaidHCV_AbQ = 'Y', 20000, 0) + IF(m.isPaidTSH = 'Y', 15500, 0) + IF(m.isPaidFT3 = 'Y', 15500, 0)
    + IF(m.isPaidFibroScan = 'Y', 60000, 0) + IF(m.isPaidMDTConsultation = 'Y', 40000, 0) + IF(m.isPaidGenotype = 'Y', 120000, 0)+ IF(m.isPaidVitD = 'Y', 15000, 0) + IF(m.isPaidFER = 'Y', 15000, 0) + IF(m.isPaidCoagulo = 'Y', 15000, 0) + IF(m.isPaidBlood = 'Y', 13500, 0)  + IF(m.isPaidBiohimi = 'Y', m.priceBiohimi, 0) + IF(m.isothercost = 'Y', m.othercostamount, 0))
    - IF(m.isCash = 'Y', m.cashamount, 0) -  IF(m.isCart = 'Y', m.cartamount, 0) - m.remainPayment - IF(m.isdiscount = 'Y', m.discountamount, 0)
    - IF(m.isresearchdisc = 'Y', m.researchdiscamount, 0) as gap,
    '' as payment

from mrlabrecord m left join doctors_main d on d.id = m.doctor where m.date = '$date'

) ma");

    if ($result != NULL)  {
            $response["status"] = "success";
            $response["message"] = "";
            $rows =  array();
            while($r =$result->fetch_assoc())
               {
                 $rows[] = $r;
               }
           if(count($rows) > 0)
            $response['data'] = json_encode($rows);
          else $response['data'] = null;
    }else {
            $response['retstatus'] = "error";
            $response['message'] = 'Хэрэглэгч бүртгэлгүй байна.';
        }
    echoResponse(200, $response);
});
//
//saveMrAPTray
//
$app->post('/saveMrAPTray', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $form =  $r->params->form;
    $response = array();
    $db = new DbHandler();
    $active = $form->Active;
    $passive = $form->Passive;
    $error = false;
    $bid = 1;
    $cid = 1;
    $did = 1;
    $adid = 1;
    $sid = 1;
    $gid = 1;
    $vid = 1;
    $ferid = 1;
    foreach ($active as $value) {
          $positionid = $value->positionid;
          $isactive = 0;
          $ubchtunid = $value->ubchtunid;
          $testtype = $value->testtype;
          switch ($testtype) {
            case 'HBV':
              $str = "UPDATE mraptray
                 SET positionid = $bid, isactive = $isactive
                 where ubchtunid = $ubchtunid and testtype = '$testtype'";
                 $bid = $bid + 1;
              break;
              case 'HCV':
              $str = "UPDATE mraptray
                 SET positionid = $cid, isactive = $isactive
                 where ubchtunid = $ubchtunid and testtype = '$testtype'";
                 $cid = $cid + 1;
              break;
              case 'HDV':
              $str = "UPDATE mraptray
                 SET positionid = $did, isactive = $isactive
                 where ubchtunid = $ubchtunid and testtype = '$testtype'";
                 $did = $did + 1;
              break;
              case 'anti_HDV':
              $str = "UPDATE mraptray
                 SET positionid = $adid, isactive = $isactive
                 where ubchtunid = $ubchtunid and testtype = '$testtype'";
                 $adid = $adid + 1;
              break;
              case 'SYSMEX':
              $str = "UPDATE mraptray
                 SET positionid = $sid, isactive = $isactive
                 where ubchtunid = $ubchtunid and testtype = '$testtype'";
                 $sid = $sid + 1;
              break;
               case 'GENOTYPE':
              $str = "UPDATE mraptray
                 SET positionid = $gid, isactive = $isactive
                 where ubchtunid = $ubchtunid and testtype = '$testtype'";
                 $gid = $gid + 1;
              break;
               case 'VITD':
              $str = "UPDATE mraptray
                 SET positionid = $vid, isactive = $isactive
                 where ubchtunid = $ubchtunid and testtype = '$testtype'";
                 $vid = $vid + 1;
              break;
               case 'FER':
              $str = "UPDATE mraptray
                 SET positionid = $ferid, isactive = $isactive
                 where ubchtunid = $ubchtunid and testtype = '$testtype'";
                 $ferid = $ferid + 1;
              break;
          }

      $result = $db->updateQuery($str);
        if ($result == NULL)  {  $error = true;  break;}
    }
    $bid = 1;
    $cid = 1;
    $did = 1;
    $adid = 1;
    $sid = 1;
    $gid = 1;
    $vid = 1;
    $ferid = 1;
    foreach ($passive as $value) {
          $positionid = $value->positionid;
          $isactive = 1;
          $ubchtunid = $value->ubchtunid;
          $testtype = $value->testtype;

          switch ($testtype) {
            case 'HBV':
              $str = "UPDATE mraptray
                 SET positionid = $bid, isactive = $isactive
                 where ubchtunid = $ubchtunid and testtype = '$testtype'";
                 $bid = $bid + 1;
              break;
              case 'HCV':
              $str = "UPDATE mraptray
                 SET positionid = $cid, isactive = $isactive
                 where ubchtunid = $ubchtunid and testtype = '$testtype'";
                 $cid = $cid + 1;
              break;
              case 'HDV':
              $str = "UPDATE mraptray
                 SET positionid = $did, isactive = $isactive
                 where ubchtunid = $ubchtunid and testtype = '$testtype'";
                 $did = $did + 1;
              break;
              case 'anti_HDV':
              $str = "UPDATE mraptray
                 SET positionid = $adid, isactive = $isactive
                 where ubchtunid = $ubchtunid and testtype = '$testtype'";
                 $adid = $adid + 1;
              break;
              case 'SYSMEX':
              $str = "UPDATE mraptray
                 SET positionid = $sid, isactive = $isactive
                 where ubchtunid = $ubchtunid and testtype = '$testtype'";
                 $sid = $sid + 1;
              break;
               case 'GENOTYPE':
              $str = "UPDATE mraptray
                 SET positionid = $gid, isactive = $isactive
                 where ubchtunid = $ubchtunid and testtype = '$testtype'";
                 $gid = $gid + 1;
              break;
              case 'VITD':
              $str = "UPDATE mraptray
                 SET positionid = $vid, isactive = $isactive
                 where ubchtunid = $ubchtunid and testtype = '$testtype'";
                 $vid = $vid + 1;
              break;
              case 'FER':
              $str = "UPDATE mraptray
                 SET positionid = $ferid, isactive = $isactive
                 where ubchtunid = $ubchtunid and testtype = '$testtype'";
                 $ferid = $ferid + 1;
              break;
          }

      $result = $db->updateQuery($str);
      if ($result == NULL)  {  $error = true; break; }
    }
    if (!$error) {
                  $response['status'] = "success";
                  $response['message'] = "Амжилттай хадгаллаа.";
            } else {
                $response['status'] = "error";
                $response['message'] = "Хадгалахад алдаа гарлаа.".$str;
            }
    echoResponse(200, $response);
});
//
//getmrapptray
//
$app->post('/getmraptray', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $testtype = $r->testtype;
    if(property_exists($r,'isactive'))
     {
      $isactive = $r->isactive;
      $result = $db->getRecord("select m.* , u.rd, u.mobile, u.firstname, u.lastname from mraptray m left join ubchtun_main u on u.id = m.ubchtunid where m.testtype = '$testtype' and m.isactive = $isactive order by m.positionid");
     }
     else $result = $db->getRecord("select m.* , u.rd, u.mobile, u.firstname, u.lastname from mraptray m left join ubchtun_main u on u.id = m.ubchtunid where m.testtype = '$testtype' order by m.positionid");

    if ($result != NULL)  {
            $response["status"] = "success";
            $response["message"] = "";
            $rows =  array();
            while($r =$result->fetch_assoc())
               {
                 $rows[] = $r;
               }
           if(count($rows) > 0)
            $response['data'] = json_encode($rows);
          else $response['data'] = null;
    }else {
            $response['retstatus'] = "error";
            $response['message'] = 'Хэрэглэгч бүртгэлгүй байна.';
        }
    echoResponse(200, $response);
});
//
// getKnowledge
//
$app->post('/getKnowledge', function() use ($app) {
  $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
  $r = json_decode($app->request->getBody());
    $systemcode = $r->systemcode;
 if(ctype_digit($systemcode)){
   $result = $db->getOneRecord("select * from survey_knowledge where ubchtunid =(select id from ubchtun_main where systemcode = '$systemcode') ");
 } else {
   $result = $db->getOneRecord("select * from survey_knowledge where ubchtunid =(select id from ubchtun_main where rd = '$systemcode') ");
 }
     if ($result != NULL) {
                $response["status"] = "success";
                $response["message"] = "";
                foreach($result as $column => $value)
                {
                    $response[$column] = $value;
                }
                echoResponse(200, $response);
        } else {
                $response["status"] = "error";
                $response["message"] = "Хэрэглэгч бүртгэлгүй байна.";
                echoResponse(201, $response);
        }
});
//
// saveRiskFactor
//
$app->post('/saveKnowledge', function() use ($app) {
  $response = array();
    $r = json_decode($app->request->getBody());
    $knowledge = $r->knowledge;
    $systemcode = $r->systemcode;
    $db = new DbHandler();
    $session = $db->getSession();
     if(ctype_digit($systemcode)){
        $rrd = $db->getOneRecord("select rd, id from ubchtun_main where systemcode = '$systemcode'");
       } else {
        $rrd = $db->getOneRecord("select rd, id from ubchtun_main where rd = '$systemcode'");
       }
    if($rrd){
              $rd= $rrd['rd'];
              $ubchtunid= $rrd['id'];
          $knowledge->rd = $rd;
          $knowledge->ubchtunid = $ubchtunid;
          $where_clause = "upper(rd)=upper('$rd')";
          $tabble_name = "survey_knowledge";
          $column_names = array('ubchtunid', 'rd', 'date', 'knowperson_hbv_hcv', 'motherbirthtochild', 'hbv_sex',
                        'hbv_hcv_forever_and_toother', 'hv_handshake', 'hv_kiss', 'hv_bloodchange',
                        'hv_medicrazorifection', 'hv_worktogether', 'hv_razorifection', 'hv_tolivercancer',
                        'hbv_vaccination_protect', 'hcv_vaccination_protect', 'hbv_hcv_todeath',
                        'test_to_hv', 'hbv_vaccination', 'hbv_vaccination_date', 'infobroadcast',
                        'lastyear_infohv', 'from_infohv');

          $isUserExists = $db->getOneRecord("select 1 from survey_knowledge where upper(rd)=upper('$rd')");
          if(!$isUserExists){
                 array_push($column_names, 'reguser', 'regdate');
                 $result = $db->insertIntoTable($knowledge, $column_names, $tabble_name);
          } else
          {
               $result = $db->updateTable($knowledge, $column_names, $tabble_name, $where_clause );
          }
           if ($result != NULL) {
                  $response["status"] = "success";
                  $response["message"] = "Амжилттай хадгаллаа.";
                  echoResponse(200, $response);
              } else {
                  $response["status"] = "error";
                  $response["message"] = "Хадгалхад алдаа гарлаа.";
                  echoResponse(201, $response);
              }
        }    else {
                $response["status"] = "error";
                $response["message"] = "Хадгалхад алдаа гарлаа. System code буруу байна.";
                echoResponse(201, $response);
        }
});

//
// getRiskFactor
//
$app->post('/getRiskFactor', function() use ($app) {
  $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $r = json_decode($app->request->getBody());
    $systemcode = $r->systemcode;
     if(ctype_digit($systemcode)){
         $result = $db->getOneRecord("select * from survey_riskfactor where ubchtunid=(select id from ubchtun_main where systemcode = '$systemcode')");
       } else {
         $result = $db->getOneRecord("select * from survey_riskfactor where ubchtunid=(select id from ubchtun_main where rd = '$systemcode')");
       }
     if ($result != NULL) {
                $response["status"] = "success";
                $response["message"] = "";
                foreach($result as $column => $value)
                {
                    $response[$column] = $value;
                }
                echoResponse(200, $response);
        } else {
                $response["status"] = "error";
                $response["message"] = "Хэрэглэгч бүртгэлгүй байна.";
                echoResponse(201, $response);
        }
});
//
// saveRiskFactor
//
$app->post('/saveRiskFactor', function() use ($app) {
  $response = array();
    $r = json_decode($app->request->getBody());
    $riskfactor = $r->riskfactor;
    //verifyRequiredParams(array('firstname', 'lastname', 'email', 'mobile','rd'),$user);
    $db = new DbHandler();
    $session = $db->getSession();
    $systemcode = $r->systemcode;
    if(ctype_digit($systemcode)){
    $rrd = $db->getOneRecord("select rd, id from ubchtun_main where systemcode = '$systemcode'");
     } else {
      $rrd = $db->getOneRecord("select rd, id from ubchtun_main where rd = '$systemcode'");
     }
    if($rrd){
        $rd= $rrd['rd'];
        $ubchtunid= $rrd['id'];
        $riskfactor->ubchtunid = $ubchtunid;
        $riskfactor->rd = $rd;
        $where_clause = "upper(rd)=upper('$rd')";
        $tabble_name = "survey_riskfactor";
        $column_names = array( 'ubchtunid', 'rd', 'date', 'treatedinhospital','surgery','homeinjection','homeinjection_type','hometwiceuseinjection','treateddental','pullteeth','wherepullteeth','fillteeth',
          'wherefillteeth','acupuncture','bloodlettingtreatment','tattoo','wheretattoo', 'useothersrazor','abortchild','whereabortchild','birthtype',
          'wherebirthtype','bloodchange','bloodfilter','family_hepatit','family_hbv_hcv','family_hbv_hcv_who','family_liver_cancer','family_liver_cancer_who','family_markertest',
          'family_markertest_positive','sickhepatitvirus','hbv_hcv','yellomark','vaccinationhbv','vaccinationhbv3time','tobacco','tobaccoyear',
          'hepatitis','cirrhosis','livercancer','liverdisease','diabetes','allergic','sicksex','bonedisease','alcohol','workpoisonplace','livertreatment','livertreatment_type');

        $isUserExists = $db->getOneRecord("select 1 from survey_riskfactor where upper(rd)=upper('$rd')");
        if(!$isUserExists){
               array_push($column_names, 'reguser', 'regdate');
               $result = $db->insertIntoTable($riskfactor, $column_names, $tabble_name);
        } else
        {
             $result = $db->updateTable($riskfactor, $column_names, $tabble_name, $where_clause );
        }
         if ($result != NULL) {
                $response["status"] = "success";
                $response["message"] = "Амжилттай хадгаллаа.";
                echoResponse(200, $response);
            } else {
                $response["status"] = "error";
                $response["message"] = "Хадгалхад алдаа гарлаа.";
                echoResponse(201, $response);
            }
        }   else {
                $response["status"] = "error";
                $response["message"] = "Хадгалхад алдаа гарлаа. System code буруу байна.";
                echoResponse(201, $response);
        }
});

//
//saveDictionry
//
$app->post('/saveDictionary', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $enword = $r->params->enword;
    $entype = $r->params->entype ? $r->params->entype : "";
    $endiscription = $r->params->endiscription ?  $r->params->endiscription: "";
    $mnword = $r->params->mnword;
    $response = array();
    $db = new DbHandler();
    $mode = $r->params->mode;
    if($mode == "new")
        {
           $result = $db->insertQuery("insert into entomndictionary (enword, entype, endiscription, mnword) values ('$enword', '$entype', '$endiscription', '$mnword')"); // $searchtext
        }
    else {
       // $result = $db->updateQuery("update table entomndictionary (enword, entype, endiscription, mnword) values ($enword, $entype, '$endiscription', '$mnword')");
    }
    if ($result != NULL)  {
              $response['status'] = "success";
              $response['message'] = '';
        }else {
            $response['status'] = "error";
            $response['message'] = '';
        }
    echoResponse(200, $response);
});
//
//getDictionry
//
$app->post('/getDictionary', function() use ($app) {
    $r = json_decode($app->request->getBody());
   // / $searchtext = $r->searchtext;
    $response = array();
    $db = new DbHandler();
    $result = $db->getRecord("select *, (select count(1) from entomndictionary) totalcount from entomndictionary"); // $searchtext
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';
          $response['totalcount'] = 0;
            $rows =  array();
            while($r =$result->fetch_assoc())
           {
            $rows[] = $r;
           }
           if(count($rows) > 0)
          {
            $response['totalcount'] = $rows[0]['totalcount'];
            $response['data'] = json_encode($rows);
          }
    }else {
            $response['status'] = "error";
            $response['message'] = '';
        }
    echoResponse(200, $response);
});
//
//  inDispenseMonosSave
//
$app->post('/inDispenseMonosSave', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $form = $r->params->form;
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];
    $rd = $form->rd;
    $dispenseid = $form->dispenseid;
    $ubchtunid = $form->ubchtunid;
    $firstname = $form->uname;
    $lastname = $form->ulname;
    $mobile = $form->umobile;
    $address = $form->uaddress;
    $druggist =  $form->druggist;
    $drugamount = $form->drugamount;
    $branch =  $form->branch;
    $doctor =  $form->doctor;
    $drugid =  $form->drugid;
    $reguser =  $userid;
    $regdate =   $form->regdate;
    $note =  $form->note;
    if($form->mode == "new"){
        $result = $db->getOneRecord("select count(1) cnt from ubchtun_main where upper(rd) = upper('$rd')");
        if ($result != NULL && $result['cnt'] == 0)  {
           $result = $db->insertQuery("insert into ubchtun_main (firstname, lastname, mobile, address, rd) values($firstname, $lastname, $mobile, '$address', $rd)");
            if ($result != NULL)
            {
               $ubchtunid = $result;
               $result = $db->insertQuery("insert into indispense (druggist, branch, doctor, drugid, drugamount, reguser, regdate, note, ubchtunid) values('$druggist', '$branch', '$doctor', $drugid, $drugamount, '$reguser', '".date("Y-m-d H:i:s")."', '$note', $ubchtunid)");
               if ($result != NULL)
                {
                      $response['status'] = "success";
                      $response['message'] = 'Амжилттай хадгаллаа.';
                      $response['id'] = $result;
                }
                else {
                       $response['status'] = "error";
                       $response['message'] = 'Хадгалахад алдаа гарлаа.';
                }
            } else {
                       $response['status'] = "error";
                       $response['message'] = 'Хадгалахад алдаа гарлаа.';
                }
        }
        else
        {
             $result = $db->insertQuery("insert into indispense (druggist, branch, doctor, drugid, drugamount, reguser, regdate, note, ubchtunid) values('$druggist', '$branch', '$doctor', $drugid, $drugamount, '$reguser', '".date("Y-m-d H:i:s")."', '$note', $ubchtunid)");
               if ($result != NULL)
                {
                      $response['status'] = "success";
                      $response['message'] = 'Амжилттай хадгаллаа.';
                      $response['id'] = $result;
                } else {
                       $response['status'] = "error";
                       $response['message'] = 'Хадгалахад алдаа гарлаа.';
                }
        }
         echoResponse(200, $response);
    }

});

//
//getListmr2000detail
//


$app->post('/getListmr2000detail', function() use ($app) {
       $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $labm2000id = $r->labm2000id;
    $result = $db->getRecord("select m.*, md.*, u.firstname, u.rd, u.lastname, u.mobile, (select count(1) from mrlabm2000detail where labm2000id = $labm2000id) totalcount from mrlabm2000detail md left join ubchtun_main u on u.id = md.ubchtunid left join mrlabrecord m on m.id = md.labrecordid where md.labm2000id = $labm2000id order by md.id");
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';

          $response['totalcount'] = 0;
            $rows =  array();
            while($r =$result->fetch_assoc())
           {
            $rows[] = $r;
           }
           if(count($rows) > 0)
          {
            $response['totalcount'] = $rows[0]['totalcount'];
            $response['data'] = json_encode($rows);
          }
    }else {
            $response['status'] = "error";
            $response['message'] = 'Бүртгэлгүй байна.';
        }
    echoResponse(200, $response);
});
//
//getListmrlabrecord
//

$app->post('/getListmrlabrecord', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $where = $r->where;
    $paging = $r->paging;
    $orderby = $r->orderby;
    if($where != "")
    {
        $result = $db->getRecord("select (select count(1) from mrlabstorage) totalcount, u.lastname, u.firstname, u.rd, ls.* from mrlabstorage ls left join ubchtun_main u on u.id = ls.ubchtunid where upper(u.lastname) like upper('%$where%') or upper(u.firstname) like upper('%$where%') or upper(u.rd) like upper('%$where%') or upper(u.systemcode) like upper('%$where%') order by ls.id DESC ".$paging);
    }
    else {
        $result = $db->getRecord("select (select count(1) from mrlabstorage) totalcount, u.lastname, u.firstname, u.rd, ls.* from mrlabstorage ls left join ubchtun_main u on u.id = ls.ubchtunid order by ls.id DESC ".$paging);
    }
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';

          $response['totalcount'] = 0;
            $rows =  array();
            while($r =$result->fetch_assoc())
           {
            $rows[] = $r;
           }
           if(count($rows) > 0)
          {
            $response['totalcount'] = $rows[0]['totalcount'];
            $response['data'] = json_encode($rows);
          } else $response['data'] = null;
    }else {
            $response['status'] = "error";
            $response['message'] = 'Бүртгэлгүй байна.'.$labbarcode.$result;
        }
    echoResponse(200, $response);
});


$app->post('/getFormInfo', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $tablename = $r->tablename;
    $id = $r->id;
    $selstr  = "";
    $joinstr = "";
    $result = $db->getRecord("select * from system_table where systemtablename = '$tablename' and fieldvisit = 'Y' order by sortid");
    if ($result != NULL)  {
          while($r =$result->fetch_assoc())
           {
            $rows[] = $r;
           }
            $arr_length = count($rows);
           if($arr_length > 0)
           {
            $response['metadata'] = $rows;
              for ($i=0; $i < $arr_length; $i++) {
                switch($rows[$i]['type'])
                {
                    case "city":
                    case "khoroo":
                    case "district":
                    case "combobox":
                    $selstr .= ", ".$rows[$i]['related'].$i.".".$rows[$i]['vfield']." ".$rows[$i]['vfield'].$rows[$i]['sortid'];
                    $joinstr .= " left join ".$rows[$i]['related']." ".$rows[$i]['related'].$i." on m.".$rows[$i]['field']." = ".$rows[$i]['related'].$i.".".$rows[$i]['rfield'];
                    break;
                }
            }
        }
           else $response['metadata'] = null;
        if($id == -1)
         {
             $str = "SELECT DISTINCT COLUMN_NAME  FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$tablename."'";
             $result = $db->getRecord($str);
             $arrayColumn = array();
              if ($result != NULL)  {
                              while($r =$result->fetch_assoc())
                               {
                                $rowsd[] = $r;
                               }
                     $arr_length = count($rowsd);
                      if($arr_length > 0)
                               {
                                  for ($i=0; $i < $arr_length; $i++) {
                                    $arrayColumn[$rowsd[$i]['COLUMN_NAME']] = "";
                                  }
                               }
                           }
            $result = $arrayColumn;
         }
         else
         {
               $str = "select m.* $selstr from $tablename m $joinstr where m.id = '$id'";
               $result = $db->getOneRecord($str);
         }
                 if ($result != NULL)  {
                    $response['status'] = "success";
                    $response['message'] = '';
                    $response['data'] = $result;
                }
    }else {
            $response['status'] = "error";
            $response['message'] = 'Бүртгэлгүй байна.';
        }
    echoResponse(200, $response);
});

$app->post('/getHypList', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid = $session["id"];
    $where = $r->where;
    $paging = $r->paging;
    $orderby = $r->orderby;
    if($where != "")
    {
        $result = $db->getRecord("select pd.istobacco, b.tc, if(b.glu > 7,1,0) glu, TIMESTAMPDIFF(YEAR, u.birthday, CURDATE()) age, u.gender, (select count(distinct ubchtunid) from mrPreliminaryExam) totalcount, round(hp.weight * 10000/(hp.height*hp.height),1) bmi, hp.weight, hp.height, hp.bp_low, hp.bp_high, hp.pulse, hp.date, u.lastname, hp.id, u.firstname, u.rd, u.systemcode, hp.ubchtunid, u.mobile from ubchtun_main u INNER JOIN mrPreliminaryExam hp on hp.ubchtunid = u.id
left join (select max(if(tc < 10, round(tc*38.6,1),tc)) tc, max(if(glu > 30, glu/18, glu)) glu, ubchtunid from sh_biohimi group by ubchtunid) b on hp.ubchtunid = b.ubchtunid
left join patient_data pd on pd.ubchtunid = hp.ubchtunid
 where upper(u.lastname) like upper('%$where%') or upper(u.firstname) like upper('%$where%') or upper(u.rd) like upper('%$where%') or upper(u.systemcode) like upper('%$where%') order by hp.id DESC ".$paging);
    }
    else {
        $result = $db->getRecord("select pd.istobacco, b.tc, if(b.glu > 7,1,0) glu, TIMESTAMPDIFF(YEAR, u.birthday, CURDATE()) age, u.gender, (select count(distinct ubchtunid) from mrPreliminaryExam) totalcount, round(hp.weight * 10000/(hp.height*hp.height),1) bmi, hp.weight, hp.height, hp.bp_low, hp.bp_high, hp.pulse, hp.date, u.lastname, hp.id, u.firstname, u.rd, u.systemcode, hp.ubchtunid, u.mobile from ubchtun_main u INNER JOIN mrPreliminaryExam hp on hp.ubchtunid = u.id
left join (select max(if(tc < 10, round(tc*38.6,1),tc)) tc, max(if(glu > 30, glu/18, glu)) glu, ubchtunid from sh_biohimi group by ubchtunid) b on hp.ubchtunid = b.ubchtunid
left join patient_data pd on pd.ubchtunid = hp.ubchtunid
 order by hp.id DESC  ".$paging);
    }
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';

          $response['totalcount'] = 0;
            $rows =  array();
            while($r =$result->fetch_assoc())
           {
            $rows[] = $r;
           }
           if(count($rows) > 0)
          {
            $response['totalcount'] = $rows[0]['totalcount'];
            $response['data'] = json_encode($rows);
          }
    }else {
            $response['status'] = "error";
            $response['message'] = 'Бүртгэлгүй байна.'.$labbarcode.$result;
        }
    echoResponse(200, $response);
});


$app->post('/getListPatient', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid = $session["id"];
   // $result = $db->getRecord("select TIMESTAMPDIFF(YEAR, u.birthday, CURDATE()) age, (select count(1) from ubchtun_main where reguser = $userid) totalcount, u.*, ht.HBsAg HBV, ht.anti_HCV HCV, ht.anti_HDV HDV, ht.ubchtunid from  ubchtun_main u left join (SELECT t2.* FROM (SELECT ubchtunid, MAX(id) AS id FROM sh_hepatittest GROUP BY ubchtunid) as t1 INNER JOIN sh_hepatittest t2 ON t1.id = t2.id) ht on ht.ubchtunid = u.id where u.reguser = $userid");
   $result = $db->getRecord("select TIMESTAMPDIFF(YEAR, u.birthday, CURDATE()) age, (select count(1) from ubchtun_main where reguser = $userid) totalcount, ht.HBsAg HBV, ht.anti_HCV HCV, ht.anti_HDV HDV, u.*  from ubchtun_main u inner join sh_hepatittest ht on ht.ubchtunid = u.id where u.reguser = $userid and ht.sourcekey = 'screen' group by u.id order by u.regdate desc");
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';

          $response['totalcount'] = 0;
            $rows =  array();
            while($r =$result->fetch_assoc())
           {
            $rows[] = $r;
           }
           if(count($rows) > 0)
          {
            $response['totalcount'] = $rows[0]['totalcount'];
            $response['data'] = json_encode($rows);
          }
    }else {
            $response['status'] = "error";
            $response['message'] = 'Бүртгэлгүй байна.'.$labbarcode.$result;
        }
    echoResponse(200, $response);
});
$app->post('/getResultBlood', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $ubchtunid = $r->ubchtunid;
    $result = $db->getOneRecord("select u.firstname, u.lastname, u.systemcode, u.rd, u.mobile, m.* from sh_blood m left join ubchtun_main u on m.ubchtunid = u.id where sourcekey = 'livercenter' and ubchtunid = $ubchtunid order by sourceid desc");
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';
        $response['doctorid'] = $result['reguser'];
        $response['firstname'] = $result['firstname'];
        $response['rd'] = $result['rd'];
        $response['lastname'] = $result['lastname'];
        $response['mobile'] = $result['mobile'];
        $response['systemcode'] = $result['systemcode'];
        $response['date'] = $result['date'];
        $response['wbc'] = $result['lekotsin'];
        $response['neu'] = $result['NEU'];
        $response['lym'] = $result['limfotsit'];
        $response['mon'] = $result['monotsit'];
        $response['eo'] = $result['eozinofil'];
        $response['bas'] = $result['bazofil'];
        $response['rbc'] = $result['eritrotsit'];
        $response['hgb'] = $result['hemoglobin'];
        $response['hct'] = $result['gematokrit'];
        $response['mcv'] = $result['MCV'];
        $response['mch'] = $result['MCH'];
        $response['mchc'] = $result['MCHC'];
        $response['neu_n'] = $result['NEU_N'];
        $response['lym_n'] = $result['LYM_N'];
        $response['mon_n'] = $result['MON_N'];
        $response['eo_n'] = $result['EO_N'];
        $response['bas_n'] = $result['BAS_N'];
        $response['rdwsd'] = $result['RDWsd'];
        $response['rdwcv'] = $result['RDWcv'];
        $response['plt'] = $result['trombotsit'];
        $response['pct'] = $result['PCT'];
        $response['mpv'] = $result['MPV'];
        $response['pdwsd'] = $result['PDWsd'];
        $response['pdwcv'] = $result['PDWcv'];
        $response['plcr'] = $result['PLCR'];
        $response['plcc'] = $result['PLCC'];
    }else {
            $response['status'] = "error";
            $response['message'] = 'Бүртгэлгүй байна.';
        }
    echoResponse(200, $response);
});

$app->post('/getResultBiohimi', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $ubchtunid = $r->ubchtunid;
    $result = $db->getOneRecord("select u.firstname, u.lastname, u.systemcode, u.rd, u.mobile, a.CRP, a.ASO, a.RF, m.* from ubchtun_main u  left join sh_biohimi m on m.ubchtunid = u.id and m.sourcekey = 'livercenter' left join sh_autoimuni a on a.ubchtunid = u.id and a.sourcekey = 'livercenter' where u.id = $ubchtunid order by m.regdate DESC, a.regdate DESC");
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';
        $response['doctorid'] = $result['reguser'];
        $response['firstname'] = $result['firstname'];
        $response['rd'] = $result['rd'];
        $response['lastname'] = $result['lastname'];
        $response['mobile'] = $result['mobile'];
        $response['systemcode'] = $result['systemcode'];
        $response['date'] = $result['date'];

        $response['CRP'] = $result['CRP'];
        $response['RF'] = $result['RF'];
        $response['ASO'] = $result['ASO'];

        $response['TBIL'] = $result['TBIL'];
        $response['DBIL'] = $result['DBIL'];
        $response['TC'] = $result['TC'];
        $response['ALP'] = $result['ALP'];
        $response['TP'] = $result['TP'];
        $response['ALB'] = $result['ALB'];
        $response['AST'] = $result['AST'];
        $response['Lipase'] = $result['Lipase'];
        $response['Tryglycerides'] = $result['Tryglycerides'];
        $response['ALT'] = $result['ALT'];
        $response['GGT'] = $result['GGT'];
        $response['LDH'] = $result['LDH'];
        $response['HDL'] = $result['HDL'];
        $response['LDL'] = $result['LDL'];
        $response['GLU'] = $result['GLU'];
        $response['CREA'] = $result['CREA'];
        $response['Lipase'] = $result['Lipase'];
        $response['BUN'] = $result['BUN'];
        $response['UA'] = $result['UA'];
        $response['P'] = $result['P'];
        $response['CI'] = $result['CI'];
        $response['Ca'] = $result['Ca'];
        $response['Mg'] = $result['Mg'];
        $response['Fe'] = $result['Fe'];
        $response['AMY'] = $result['AMY'];
        $response['HemoglobinA1c'] = $result['HemoglobinA1c'];

    }else {
            $response['status'] = "error";
            $response['message'] = 'Бүртгэлгүй байна.';
        }
    echoResponse(200, $response);
});
$app->post('/getRecordmrLabm2000', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $labbarcode = $r->labbarcode;
    $testtype = $r->testtype;
    if($labbarcode == "" && $r->rd != ""){
      $rd = $r->rd;
      $date = $r->date;
      $result = $db->getOneRecord("select m2.*,u.systemcode, u.lastname, u.firstname, u.rd, u.mobile, ". ($testtype == "HBV" ? "n.HBV_DNA": ($testtype == "HCV" ? "n.HCV_RNA" : ($testtype == "HDV" ? "n.HDV_RNA" : "1")))." from mrlabm2000 m2 left join mrlabm2000detail m2d on m2d.labm2000id = m2.id left join ubchtun_main u on m2d.ubchtunid = u.id left join sh_nuklein n on  n.sourcekey = 'mrlabrecord' and n.sourceid = m2d.labrecordid  and n.ubchtunid = m2d.ubchtunid where u.rd =  '$rd' and upper(m2.testtype) = upper('$testtype') and m2.date >= '$date'");
    } else{
    $result = $db->getOneRecord("select m2.*,u.systemcode, u.rd, u.mobile from mrlabm2000 m2 left join mrlabm2000detail m2d on m2d.labm2000id = m2.id left join ubchtun_main u on m2d.ubchtunid = u.id where substring(m2d.barcode,1,14)  = substring('$labbarcode',1,14) and upper(m2.testtype) = upper('$testtype')");
  }
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';
        $response['id'] = $result['id'];
        $response['labid'] = $result['labid'];
        $response['testtype'] = $result['testtype'];
        $response['date'] = $result['date'];
        $response['runtime'] = $result['runtime'];
        $response['platename'] = $result['platename'];
        $response['reguser'] = $result['reguser'];
        $response['dwplatename'] = $result['dwplatename'];
        $response['doctorid'] = $result['doctorid'];
        $response['serlot'] = $result['serlot'];
        $response['serexpiration'] = $result['serexpiration'];
        $response['sectime'] = $result['sectime'];
        $response['mmactime'] = $result['mmactime'];
        $response['controllot'] = $result['controllot'];
        $response['controllevels'] = $result['controllevels'];
        $response['calibratorlot'] = $result['calibratorlot'];
        $response['actime'] = $result['actime'];
        $response['calibratorlevels'] = $result['calibratorlevels'];
        $response['rd'] = $result['rd'];
        $response['systemcode'] = $result['systemcode'];
        $response['mobile'] = $result['mobile'];

        $response['pcrrexpiration'] = $result['pcrrexpiration'];
        $response['pcrrlot'] = $result['pcrrlot'];
        $response['assaylot'] = $result['assaylot'];
        $response['qclot'] = $result['qclot'];
        $response['result'] = $testtype == "" ? "" : ($testtype == "HBV" ? $result['HBV_DNA'] : ($testtype == "HCV" ? $result['HCV_RNA'] : ($testtype == "HDV" ? $result['HDV_RNA'] : 1)));
    }else {
            $response['status'] = "error";
            $response['message'] = 'Бүртгэлгүй байна.'.$labbarcode.$result;
        }
    echoResponse(200, $response);
});
$app->post('/getmrLabm2000', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $labm2000id = $r->labm2000id;
    $result = $db->getOneRecord("select * from mrlabm2000 where id = '$labm2000id'");
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';
        $response['labid'] = $result['labid'];
        $response['testtype'] = $result['testtype'];
        $response['date'] = $result['date'];
        $response['runtime'] = $result['runtime'];
        $response['platename'] = $result['platename'];
        $response['reguser'] = $result['reguser'];
        $response['dwplatename'] = $result['dwplatename'];
        $response['doctorid'] = $result['doctorid'];
        $response['serlot'] = $result['serlot'];
        $response['serexpiration'] = $result['serexpiration'];
        $response['sectime'] = $result['sectime'];
        $response['mmactime'] = $result['mmactime'];
        $response['controllot'] = $result['controllot'];
        $response['controllevels'] = $result['controllevels'];
        $response['calibratorlot'] = $result['calibratorlot'];
        $response['actime'] = $result['actime'];
        $response['calibratorlevels'] = $result['calibratorlevels'];

        $response['pcrrexpiration'] = $result['pcrrexpiration'];
        $response['pcrrlot'] = $result['pcrrlot'];
        $response['assaylot'] = $result['assaylot'];
        $response['qclot'] = $result['qclot'];
    }else {
            $response['status'] = "error";
            $response['message'] = 'Бүртгэлгүй байна.';
        }
    echoResponse(200, $response);
});

$app->post('/getmrLabStorageNew', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];
    $result = $db->getOneRecord("select positionid from mrlabstorage order by id desc");
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';
        $response['positionid'] = $result['positionid'];
    }else {
            $response['status'] = "error";
            $response['message'] = 'Сериал код буруу байна.';
        }
    echoResponse(200, $response);
});

$app->post('/getmrLabRecord', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $labrecordid = $r->labrecordid;
    if($labrecordid == 0 && property_exists($r,'barcode'))
    { $barcode = $r->barcode;
      $testtype = $r->testtype;
      switch ($testtype) {
        case 'HBV':
          $result = $db->getOneRecord("select *, (select mobile from ubchtun_main where id = ubchtunid) mobile, (select rd from ubchtun_main where id = ubchtunid) rd from mrlabrecord where labbarcode = substring('$barcode',1,14) and isHBV_DNA = 'Y' and isPaidHBV_DNA = 'Y'");
          break;
        case 'HCV':
          $result = $db->getOneRecord("select *, (select mobile from ubchtun_main where id = ubchtunid) mobile, (select rd from ubchtun_main where id = ubchtunid) rd from mrlabrecord where labbarcode = substring('$barcode',1,14) and isPaidHCV_DNA = 'Y' and isHCV_DNA = 'Y'");
          break;
        case 'HDV':
          $result = $db->getOneRecord("select *, (select mobile from ubchtun_main where id = ubchtunid) mobile, (select rd from ubchtun_main where id = ubchtunid) rd from mrlabrecord where labbarcode = substring('$barcode',1,14) and isPaidHDV_DNA = 'Y' and isHDV_DNA = 'Y'");
          break;
        case 'anti_HDV':
          $result = $db->getOneRecord("select *, (select mobile from ubchtun_main where id = ubchtunid) mobile, (select rd from ubchtun_main where id = ubchtunid) rd from mrlabrecord where labbarcode = substring('$barcode',1,14) and isPaidHDV_Ab = 'Y' and isHDV_Ab = 'Y'");
          break;
        case 'SYSMEX':
          $result = $db->getOneRecord("select *, (select mobile from ubchtun_main where id = ubchtunid) mobile, (select rd from ubchtun_main where id = ubchtunid) rd from mrlabrecord where labbarcode = substring('$barcode',1,14) and ((isPaidHBsAgQ = 'Y' and isHBsAgQ = 'Y') or (isPaidHBeAg = 'Y' and isHBeAg = 'Y') or (isPaidaHBs = 'Y' and isaHBs = 'Y') or (isPaidM2BPGI = 'Y' and isM2BPGI = 'Y') or (isPaidAFP = 'Y' and isAFP = 'Y') or (isPaidVitD = 'Y' and isVitD = 'Y') or (isPaidFER = 'Y' and isFER = 'Y') or (isPaidGenotype = 'Y' and isGenotype = 'Y') or (isPaidHCV_AbQ = 'Y' and isHCV_AbQ = 'Y') or (isPaidTSH = 'Y' and isTSH = 'Y') or (isPaidFT3 = 'Y' and isFT3 = 'Y') or (isPaidFT4 = 'Y' and isFT4 = 'Y'))");
          break;
        case 'VitD':
           $result = $db->getOneRecord("select *, (select mobile from ubchtun_main where id = ubchtunid) mobile, (select rd from ubchtun_main where id = ubchtunid) rd from mrlabrecord where labbarcode = substring('$barcode',1,14) and isPaidVitD = 'Y' and isVitD = 'Y'");
          break;
        case 'FER':
           $result = $db->getOneRecord("select *, (select mobile from ubchtun_main where id = ubchtunid) mobile, (select rd from ubchtun_main where id = ubchtunid) rd from mrlabrecord where labbarcode = substring('$barcode',1,14) and isPaidFER = 'Y' and isFER = 'Y'");
          break;
      }
    }
    else $result = $db->getOneRecord("select *, (select mobile from ubchtun_main where id = ubchtunid) mobile,  (select rd from ubchtun_main where id = ubchtunid) rd from mrlabrecord where id ='$labrecordid'");
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';
        $response["biohimi"] = [];
        $response['ubchtunid'] = $result['ubchtunid'];
        $response['note'] = $result['note'];
        $response['date'] = $result['date'];
        $response['rd'] = $result['rd'];
        $response['labrecordid'] = $result['id'];
        $response['mobile'] = $result['mobile'];


        $response['isSecondDoctor'] = $result['isSecondDoctor'];
        $response['isHBsAg'] = $result['isHBsAg'];
        $response['isHCV_Ab'] = $result['isHCV_Ab'];
        $response['isHIV'] = $result['isHIV'];
        $response['isSyphilis'] = $result['isSyphilis'];
        $response['isHDV_Ab'] = $result['isHDV_Ab'];
        $response['isHBV_DNA'] = $result['isHBV_DNA'];
        $response['isHCV_DNA'] = $result['isHCV_DNA'];
        $response['isHDV_DNA'] = $result['isHDV_DNA'];
        $response['isPaidHBsAg'] = $result['isPaidHBsAg'];
        $response['isPaidHCV_Ab'] = $result['isPaidHCV_Ab'];
        $response['isPaidHIV'] = $result['isPaidHIV'];
        $response['isPaidSyphilis'] = $result['isPaidSyphilis'];
        $response['isPaidHDV_Ab'] = $result['isPaidHDV_Ab'];
        $response['isPaidHBV_DNA'] = $result['isPaidHBV_DNA'];
        $response['isPaidHCV_DNA'] = $result['isPaidHCV_DNA'];
        $response['isPaidHDV_DNA'] = $result['isPaidHDV_DNA'];
        $response['isResHBsAg'] = $result['isResHBsAg'];
        $response['isResHCV_Ab'] = $result['isResHCV_Ab'];
        $response['isResHIV'] = $result['isResHIV'];
        $response['isResSyphilis'] = $result['isResSyphilis'];
        $response['isResHDV_Ab'] = $result['isResHDV_Ab'];
        $response['isResHBV_DNA'] = $result['isResHBV_DNA'];
        $response['isResHCV_DNA'] = $result['isResHCV_DNA'];
        $response['isResHDV_DNA'] = $result['isResHDV_DNA'];
        $response['HBsAg'] = $result['HBsAg'];
        $response['HCV_Ab'] = $result['HCV_Ab'];
        $response['HIV'] = $result['HIV'];
        $response['Syphilis'] = $result['Syphilis'];
        $response['HDV_Ab'] = $result['HDV_Ab'];
        $response['HBV_DNA_ul'] = $result['HBV_DNA_ul'];
        $response['HBV_DNA_s'] = $result['HBV_DNA_s'];
        $response['HCV_DNA'] = $result['HCV_DNA'];
        $response['HDV_DNA'] = $result['HDV_DNA'];
        $response['labbarcode'] = $result['labbarcode'];
        $response['labid'] = $result['labid'];
        $response['isPaidAll'] = $result['isPaidAll'];
        $response['remainPayment'] = $result['remainPayment'];

        $response['isDiscFibroscan'] = $result['isDiscFibroscan'];
        $response['cartamount'] = $result['cartamount'];
        $response['isCash'] = $result['isCash'];
        $response['isCart'] = $result['isCart'];
        $response['cashamount'] = $result['cashamount'];
        $response['isdiscount'] = $result['isdiscount'];
        $response['discountamount'] = $result['discountamount'];
        $response['isPaidEMD'] = $result['isPaidEMD'];
        $response['PaidEMD'] = $result['PaidEMD'];
        $response['isresearchdisc'] = $result['isresearchdisc'];
        $response['researchdiscamount'] = $result['researchdiscamount'];
        $response['isothercost'] = $result['isothercost'];
        $response['othercostamount'] = $result['othercostamount'];


          $response['HBsAgQ'] = $result['HBsAgQ'];
          $response['HCV_AbQ'] = $result['HCV_AbQ'];
          $response['TSH'] = $result['TSH'];
          $response['FT3'] = $result['FT3'];
          $response['FT4'] = $result['FT4'];
          $response['HBeAg'] = $result['HBeAg'];
          $response['aHBs'] = $result['aHBs'];
          $response['M2BPGI'] = $result['M2BPGI'];
          $response['AFP'] = $result['AFP'];
          $response['VitD'] = $result['VitD'];
          $response['FER'] = $result['FER'];
          $response['FibroScan'] = $result['FibroScan'];
          $response['MDTConsultation'] = $result['MDTConsultation'];
          $response['isHBsAgQ'] = $result['isHBsAgQ'];
          $response['isHCV_AbQ'] = $result['isHCV_AbQ'];
          $response['isTSH'] = $result['isTSH'];
          $response['isFT3'] = $result['isFT3'];
          $response['isFT4'] = $result['isFT4'];
          $response['isHBeAg'] = $result['isHBeAg'];
          $response['isaHBs'] = $result['isaHBs'];
          $response['isM2BPGI'] = $result['isM2BPGI'];
          $response['isAFP'] = $result['isAFP'];
          $response['isVitD'] = $result['isVitD'];
          $response['isFER'] = $result['isFER'];
          $response['isFibroScan'] = $result['isFibroScan'];
          $response['isMDTConsultation'] = $result['isMDTConsultation'];
          $response['isPaidHBsAgQ'] = $result['isPaidHBsAgQ'];
          $response['isPaidHCV_AbQ'] = $result['isPaidHCV_AbQ'];
          $response['isPaidTSH'] = $result['isPaidTSH'];
          $response['isPaidFT3'] = $result['isPaidFT3'];
          $response['isPaidFT4'] = $result['isPaidFT4'];
          $response['isPaidHBeAg'] = $result['isPaidHBeAg'];
          $response['isPaidaHBs'] = $result['isPaidaHBs'];
          $response['isPaidM2BPGI'] = $result['isPaidM2BPGI'];
          $response['isPaidAFP'] = $result['isPaidAFP'];
          $response['isPaidVitD'] = $result['isPaidVitD'];
          $response['isPaidFER'] = $result['isPaidFER'];
          $response['isPaidFibroScan'] = $result['isPaidFibroScan'];
          $response['isPaidMDTConsultation'] = $result['isPaidMDTConsultation'];
          $response['isResHBsAgQ'] = $result['isResHBsAgQ'];
          $response['isResHCV_AbQ'] = $result['isResHCV_AbQ'];
          $response['isResTSH'] = $result['isResTSH'];
          $response['isResFT3'] = $result['isResFT3'];
          $response['isResFT4'] = $result['isResFT4'];
          $response['isResHBeAg'] = $result['isResHBeAg'];
          $response['isResaHBs'] = $result['isResaHBs'];
          $response['isResM2BPGI'] = $result['isResM2BPGI'];
          $response['isResAFP'] = $result['isResAFP'];
          $response['isResVitD'] = $result['isResVitD'];
          $response['isResFER'] = $result['isResFER'];
          $response['isResFibroScan'] = $result['isResFibroScan'];
          $response['isResMDTConsultation'] = $result['isResMDTConsultation'];

            $response['Genotype'] = $result['Genotype'];
            $response['isGenotype'] = $result['isGenotype'];
            $response['isResGenotype'] = $result['isResGenotype'];
            $response['isPaidGenotype'] = $result['isPaidGenotype'];
            $response['Blood'] = $result['Blood'];
            $response['isBlood'] = $result['isBlood'];
            $response['isResBlood'] = $result['isResBlood'];
            $response['isPaidBlood'] = $result['isPaidBlood'];
            $response['Coagulo'] = $result['Coagulo'];
            $response['isCoagulo'] = $result['isCoagulo'];
            $response['isResCoagulo'] = $result['isResCoagulo'];
            $response['isPaidCoagulo'] = $result['isPaidCoagulo'];
            $response['Biohimi'] = $result['Biohimi'];
            $response['isBiohimi'] = $result['isBiohimi'];
            $response['isResBiohimi'] = $result['isResBiohimi'];
            $response['isPaidBiohimi'] = $result['isPaidBiohimi'];


         $response['isDoctor'] = $result['isDoctor'];
         $response['isPaidDoctor'] = $result['isPaidDoctor'];
         $response['isResDoctor'] = $result['isResDoctor'];
         $response['Doctor'] = $result['Doctor'];

    } else {
            $response['status'] = "error";
            $response['message'] = 'Хэрэглэгч бүртгэлгүй байна.';
        }
        if($response['status'] != "error" && $response['isBiohimi'] == "Y"){
           $result = $db->getRecord("select * from mrlabrecorddetail where labrecordid ='$labrecordid'");
           if ($result != NULL)  {
                $response['status'] = "success";
                $response['message'] = '';
                 foreach($result as $column => $value)
                {
                    $response["biohimi"][$column] = $value;
                }
           }
      }
    echoResponse(200, $response);
});

$app->post('/comecheckserialcode', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];
    $serialcode = $r->serialcode;
    $result = $db->getOneRecord("select count(*) cnt from incomedetail where drugserialnumber = '$serialcode'");
    if ($result != NULL && $result['cnt'] == 0)  {
        $response['status'] = "success";
        $response['message'] = '';
    }else {
            $response['status'] = "error";
            $response['message'] = 'Сериал код буруу байна.';
        }
    echoResponse(200, $response);
});


$app->post('/transfercheckserialcode', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];
    $serialcode = $r->serialcode;
    $result = $db->getOneRecord("select count(*) cnt from instockdiarydetail where drugserialnumber = '$serialcode'");
    if ($result != NULL && $result['cnt'] == 0)  {
        $response['status'] = "success";
        $response['message'] = '';
    }else {
            $response['status'] = "error";
            $response['message'] = 'Сериал код буруу байна.';
        }
    echoResponse(200, $response);
});
//
// Hypertension Dashboard
//
$app->post('/getDashboardHyp', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];
    $result = $db->getOneRecord("select count(*) allpatient, sum(if(bp_high >139,1,0)) allpatienthyp, sum(if(round(weight * 10000/(height*height),1)>25,1,0)) bmi,(select count(1) from (select b.ubchtunid from sh_biohimi b where b.tc is not null and b.tc != '' and b.glu is not null and b.glu != '' group by b.ubchtunid) bb inner join (select ubchtunid from patient_data where tobacco is not null and tobacco !='' group by ubchtunid) p on p.ubchtunid = bb.ubchtunid left join (select ubchtunid from mrPreliminaryExam) pe on pe.ubchtunid = bb.ubchtunid) heartscore from mrPreliminaryExam where bp_high > 0");
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';
        $response['allpatienthyp'] = $result['allpatienthyp'];
        $response['allpatient'] = $result['allpatient'];
        $response['bmi'] = $result['bmi'];
        $response['heartscore'] = $result['heartscore'];
    } else {
            $response['status'] = "error";
            $response['message'] = 'Dashboard';
        }
    echoResponse(200, $response);
});
//
// Lab Dashboard
//
$app->post('/getDashboardLab', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];
    $result = $db->getOneRecord("select (select sum(if(anti_HDV ='Y', 1,0))  from sh_hepatittest) countd, (select sum(if(hbsag ='Y', 1,0))  from sh_hepatittest) countb, (select sum(if(anti_HCV ='Y', 1,0))  from sh_hepatittest) countc, (select count(*)  from doctors_main where labid = 1) doctorlab, count(*) patient7lab, (select count(DISTINCT ubchtunid)  from mrlabrecord) allpatientlab, (select count(*) from
      ubchtun_main) allpatient from ubchtun_main where (regdate between '".date('Y-m-d H:i:s', strtotime('-7 days'))."' and '".date('Y-m-d H:i:s')."')");
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';
        $response['doctorlab'] = $result['doctorlab'];
        $response['patient7lab'] = $result['patient7lab'];
        $response['allpatientlab'] = $result['allpatientlab'];
        $response['allpatient'] = $result['allpatient'];
        $response['countb'] = $result['countb'];
        $response['countc'] = $result['countc'];
        $response['countd'] = $result['countd'];
        $response['percentb'] = $result['countb']* 100/$result['allpatient'];
        $response['percentc'] = $result['countc']*100/$result['allpatient'];
        $response['percentd'] = $result['countd']*100/$result['allpatient'];

         $rst = $db->getRecord("SELECT a.id, IFNULL(Sum(tr.cnt),0) cnt FROM 
                                (select 5 as id union all select 6 union all select 10 union all select 174 union all select 186 union all select 192 union all select 339) as a
                                left join(SELECT reguser, Count(1) cnt FROM mrtreatment WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser
                                UNION 
                                SELECT reguser, Count(1) cnt FROM mrprescription WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser
                                UNION 
                                SELECT reguser, Count(1) cnt FROM mrdiagnosis WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser
                                UNION 
                                SELECT reguser, Count(1) cnt FROM mrdrugsideeffect WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser) tr on a.id = tr.reguser  GROUP BY a.id order by id"); // 12
                foreach($rst as $column => $value)
                {
                    $response["tr"][$column] = $value;
                }
                $rst = $db->getRecord("SELECT a.id, IFNULL(Sum(ex.cnt),0) cnt FROM (select 5 as id union all select 6 union all select 10 union all select 174 union all select 186 union all select 192 union all select 339) as a
                                        left join
                                        (
                                        SELECT reguser, Count(1) cnt FROM em_ubchniituuh WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser
                                        UNION 
                                        SELECT reguser, Count(1) cnt FROM em_harshildagem WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser
                                        UNION 
                                        SELECT reguser, Count(1) cnt FROM em_meszasal WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser
                                        UNION 
                                        SELECT reguser, Count(1) cnt FROM mrPatientExam WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser
                                        UNION 
                                        SELECT reguser, Count(1) cnt FROM mrPatientPain WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser
                                        UNION 
                                        SELECT reguser, Count(1) cnt FROM mrPreliminaryExam WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser) ex on a.id = ex.reguser  GROUP BY a.id order by id"); // 12
                        foreach($rst as $column => $value)
                        {
                            $response["ex"][$column] = $value;
                        }
            $rst = $db->getRecord("SELECT a.id, IFNULL(Sum(sh.cnt),0) cnt FROM (select 5 as id union all select 6 union all select 10 union all select 174 union all select 186 union all select 192 union all select 339) as a
                                    left join
                                    (
                                    SELECT reguser, Count(1) cnt FROM sh_autoimuni WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser
                                    UNION 
                                    SELECT reguser, Count(1) cnt FROM sh_biohimi WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser
                                    UNION 
                                    SELECT reguser, Count(1) cnt FROM sh_blood WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser
                                    UNION 
                                    SELECT reguser, Count(1) cnt FROM sh_coagulogramm WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser
                                    UNION 
                                    SELECT reguser, Count(1) cnt FROM sh_diabit WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser
                                    UNION 
                                    SELECT reguser, Count(1) cnt FROM sh_fibroscan WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser
                                    UNION 
                                    SELECT reguser, Count(1) cnt FROM sh_hepatittest WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser
                                    UNION 
                                    SELECT reguser, Count(1) cnt FROM sh_nuklein WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser
                                    UNION 
                                    SELECT reguser, Count(1) cnt FROM sh_other WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser
                                    UNION 
                                    SELECT reguser, Count(1) cnt FROM sh_shees WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser
                                    UNION 
                                    SELECT reguser, Count(1) cnt FROM sh_sismeks WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser
                                    UNION 
                                    SELECT reguser, Count(1) cnt FROM sh_visualdiagnosis WHERE regdate BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() OR updated BETWEEN Date_sub(Curdate(), INTERVAL 10 day) AND Curdate() GROUP BY reguser) sh on a.id = sh.reguser  GROUP BY a.id order by id"); // 12
                        foreach($rst as $column => $value)
                        {
                            $response["sh"][$column] = $value;
                        }

    }else {
            $response['status'] = "error";
            $response['message'] = 'Dashboard';
        }
    echoResponse(200, $response);
});
$app->post('/getDashboardLab_patientlab', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];
    $result = $db->getRecord("select DATE_FORMAT(a.Date, '%a') AS date,
       COALESCE((SELECT COUNT(id)
                 FROM ubchtun_main
                 WHERE substring(regdate,1,10) = a.Date), 0) as COUNTPATIENT,
       COALESCE((SELECT COUNT(id)
                 FROM mrlabrecord
                 WHERE substring(regdate,1,10) = a.Date), 0) as COUNTLABRECORD
        from (
            select curdate() - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY as Date
            from (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as a
            cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as b
            cross join (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as c
        ) a
        where a.Date between DATE_SUB(CURDATE(), INTERVAL 10 DAY) and CURDATE()
        ORDER BY a.Date");
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';
         while($r = $result->fetch_assoc())
           {
            $rows[] = $r;
           }
           if(count($rows) > 0)
            $response['data'] = json_encode($rows);
            //-----------
            $rst= $db->getRecord("select sum(if(m.ispaiddoctor = 'Y',1,0)) Doctor,
                                sum(if(m.ispaidHBsAg = 'Y',1,0)) HBsAg,
                                sum(if(m.isPaidHCV_Ab = 'Y',1,0)) HCV_Ab,
                                sum(if(m.ispaidHIV = 'Y',1,0)) HIV,
                                sum(if(m.isPaidSyphilis = 'Y',1,0)) Syphilis,
                                sum(if(m.isPaidHDV_Ab = 'Y',1,0))  HDV_Ab,
                                sum(if(m.isPaidHBV_DNA = 'Y',1,0))  HBV_DNA,
                                sum(if(m.isPaidHCV_DNA = 'Y',1,0))  HCV_DNA,
                                sum(if(m.isPaidHDV_DNA = 'Y',1,0))  HDV_DNA,
                                sum(if(m.isPaidHBsAgQ = 'Y',1,0))  HBsAgQ,
                                sum(if(m.isPaidHCV_AbQ = 'Y',1,0))  HCV_AbQ,
                                sum(if(m.isPaidHBeAg = 'Y',1,0))  HBeAg,
                                sum(if(m.isPaidaHBs = 'Y',1,0))  aHBs,
                                sum(if(m.isPaidM2BPGI = 'Y',1,0))  M2BPGI,
                                sum(if(m.isPaidAFP = 'Y',1,0))  AFP,
                                sum(if(m.isPaidTSH = 'Y',1,0))  TSH,
                                sum(if(m.isPaidFT3 = 'Y',1,0))  FT3,
                                sum(if(m.isPaidFT4 = 'Y',1,0))  FT4,
                                sum(if(m.isPaidFibroScan = 'Y',1,0))  FibroScan,
                                sum(if(m.isPaidVitD = 'Y',1,0))  VitD,
                                sum(if(m.isPaidFER = 'Y',1,0))  FER,
                                sum(if(m.isPaidBiohimi = 'Y',1,0))  Biohimi,
                                sum(if(m.isPaidBlood = 'Y',1,0))  Blood,
                                 sum(if(m.isPaidCoagulo = 'Y',1,0))  Coagulo,
                                sum(if(m.isPaidGenotype = 'Y',1,0))  Genotype
                                from mrlabrecord m
                                where m.date between DATE_SUB(CURDATE(), INTERVAL 10 DAY)  and CURDATE()");
                if ($rst != NULL)  {
                    foreach($rst as $column => $value)
                            {
                                $response["service"][$column] = $value;
                            }

                            $dres = $db->getRecord("select concat(substr(d.lastname,1,1),'.',d.firstname) label, count(d.firstname) data from mrlabrecord m inner join doctors_main d on d.id = m.doctor 
                            where m.ispaiddoctor = 'Y' and m.date between DATE_SUB(CURDATE(), INTERVAL 10 DAY)  and CURDATE() group by m.doctor");
                                if ($dres != NULL)  {
                                    foreach($dres as $column => $value)
                                            {
                                                $response["doctor"][$column] = $value;
                                            }
                                }
                }
            //----------------

    }else {
            $response['status'] = "error";
            $response['message'] = 'Dashboard';
        }
    echoResponse(200, $response);
});

$app->post('/getDashboardDrug', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];
    $result = $db->getOneRecord("select sum(s.unitqty) alldruginbranch, count(drugid) druginbranch, (select count(id) from indispense) patientsinbranch, (select count(id) from doctors_main where branchid = b.id ) workersinbranch
      from instock s inner join inbranch b on b.`id` = s.`branchid`  where b.id = (select branchid from doctors_main where userid = '$userid')");
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';
        $response['alldruginbranch'] = $result['alldruginbranch'];
        $response['druginbranch'] = $result['druginbranch'];
        $response['patientsinbranch'] = $result['patientsinbranch'];
        $response['workersinbranch'] = $result['workersinbranch'];

    }else {
            $response['status'] = "error";
            $response['message'] = 'Dashboard';
        }
    echoResponse(200, $response);
});
$app->post('/checkUserInfo', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $rd = $r->rd;
    if($rd != "") $result = $db->getOneRecord("select (select COUNT(1)
 from mrlabrecord where (`isPaidHBV_DNA` = 'Y' or `isPaidHCV_DNA` = 'Y' or `isPaidHDV_DNA` = 'Y') and ubchtunid = u.id) cnt, TIMESTAMPDIFF(YEAR, u.birthday, CURDATE()) age, u.* from ubchtun_main u where LOWER(u.rd) = LOWER('$rd')");
    else { $systemcode = $r->systemcode;
           $result = $db->getOneRecord("select TIMESTAMPDIFF(YEAR, u.birthday, CURDATE()) age, u.* from ubchtun_main u where LOWER(u.systemcode) = LOWER('$systemcode')"); }
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';
        $response['ubchtunid'] = $result['id'];
        $response['cnt'] = isset($result['cnt']) ? $result['cnt'] : "";
        $response['lastname'] = $result['lastname'];
        $response['firstname'] = $result['firstname'];
        $response['rd'] = $result['rd'];
        $response['age'] = $result['age'];
        $response['gender'] = $result['gender'];
        $response['mobile'] = $result['mobile'];
        $response['email'] = $result['email'];
        $response['address'] = $result['address'];
        $response['systemcode'] = $result['systemcode'];
    }else {
            $response['status'] = "error";
            $response['message'] = 'Хэрэглэгч бүртгэлгүй байна.';
        }
    echoResponse(200, $response);
});

$app->post('/checkDoctorInfo', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $userid = $r->userid;
    $result = $db->getOneRecord("select * from doctors_main where LOWER(userid) = LOWER('$userid')");
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';
         $response['doctorid'] = $result['id'];
        $response['lastname'] = $result['lastname'];
        $response['firstname'] = $result['firstname'];
        $response['rd'] = $result['rd'];
        $response['mobile'] = $result['mobile'];
        $response['email'] = $result['email'];

    }else {
            $response['status'] = "error";
            $response['message'] = 'Эмч бүртгэлгүй байна.';
        }
    echoResponse(200, $response);
});


$app->post('/getinDispense', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $dispenseid = $r->dispenseid;
    $result = $db->getOneRecord("select d.* , u.rd, u.firstname, u.lastname, u.mobile, u.address from indispense d left join ubchtun_main u on u.id = d.ubchtunid where d.id = '$dispenseid'");
    if ($result != NULL)  {
        $response['retstatus'] = "success";
        $response['retmessage'] = '';
        $response['ubchtunid'] = $result['ubchtunid'];
        $response['note'] = $result['note'];
        $response['regdate'] = $result['regdate'];
        $response['rd'] = $result['rd'];
        $response['firstname'] = $result['firstname'];
        $response['lastname'] = $result['lastname'];
        $response['mobile'] = $result['mobile'];
        $response['address'] = $result['address'];

        $response['branch'] = $result['branch'];
        $response['druggist'] = $result['druggist'];
        $response['doctor'] = $result['doctor'];
        $response['drugid'] = $result['drugid'];
        $response['drugamount'] = $result['drugamount'];

    }else {
            $response['retstatus'] = "error";
            $response['message'] = 'Хэрэглэгч бүртгэлгүй байна.';
        }
    echoResponse(200, $response);
});

$app->post('/getinCome', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $comeid = $r->comeid;
    $result = $db->getOneRecord("select * from income where id = '$comeid'");
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';
        $response['drugsupplierid'] = $result['drugsupplierid'];
        $response['distributerid'] = $result['distributerid'];
        $response['date'] = $result['date'];
        $response['drugid'] = $result['drugid'];
        $response['unitqty'] = $result['totalqty'];
        $response['reguser'] = $result['reguser'];
        $response['note'] = $result['note'];
    }else {
            $response['status'] = "error";
            $response['message'] = 'Хэрэглэгч бүртгэлгүй байна.';
        }
    echoResponse(200, $response);
});

$app->post('/getinTransfer', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $stockdiaryid = $r->stockdiaryid;
    $result = $db->getOneRecord("select * from instockdiary where id = '$stockdiaryid'");
    if ($result != NULL)  {
        $response['retstatus'] = "success";
        $response['retmessage'] = '';
        $response['fromstockid'] = $result['fromstockid'];
        $response['tostockid'] = $result['tostockid'];
        $response['date'] = $result['date'];
        $response['status'] = $result['status'];
        $response['drugid'] = $result['drugid'];
        $response['initialqty'] = $result['initialqty'];
        $response['unitqty'] = $result['unitqty'];
        $response['reguser'] = $result['reguser'];
        $response['note'] = $result['note'];
    }else {
            $response['retstatus'] = "error";
            $response['message'] = 'Хэрэглэгч бүртгэлгүй байна.';
        }
    echoResponse(200, $response);
});
$app->post('/getCombo', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('table'),$r->params);
    $db = new DbHandler();
    $table = $r->params->table;
    $related = $r->params->related;
    $where = $r->params->where;
      $id = $r->params->id;
        $name = $r->params->name;
    $str = "select `".$id."`, `".$name."` from `".$table."`".$where;
   $mrconstant = $db->getRecord($str);
    if($mrconstant != NULL){
            $response["status"] = "success";
            $response["message"] = "";
            $rows =  array();
            while($r =$mrconstant->fetch_assoc())
           {
            $rows[] = $r;
           }
           if(count($rows) > 0)
            $response['data'] = json_encode($rows);
            echoResponse(200, $response);
    }else{
        $response["status"] = "error";
        $response["message"] = "Утга авахад алдаа гарлаа!";
        echoResponse(201, $response);
    }
});
$app->get('/getMenu', function() use ($app) {
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];

    $str = "select mg.title mname , mg.icon miconCls , m.title sname, m.iconCls siconCls, m.url surl
    from menugroup mg left join menu m on m.menugroupid = mg.id where mg.menutype = 'main' and instr
    (m.usergroupid,(select usergroupid from doctors_main where userid = '".$userid."')) > 0 order by mg.sortid, mg.title";
    $mrconstant = $db->getRecord($str);
    if($mrconstant != NULL){
            $response["status"] = "success";
            $response["message"] = "";
            $rows =  array();
            while($r =$mrconstant->fetch_assoc())
           {
            $rows[] = $r;
           }
           if(count($rows) > 0)
            $response['data'] = json_encode($rows);
            echoResponse(200, $response);
    }else{
        $response["status"] = "error";
        $response["message"] = "Утга авахад алдаа гарлаа!";
        echoResponse(201, $response);
    }
});

$app->post('/getPermission', function() use ($app) {
     $r = json_decode($app->request->getBody());
    //verifyRequiredParams(array('email', 'name', 'password'),$r->customer);
    $tablename = $r->tablename;

    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];

    $str = "select
instr(uservisit,(select usergroupid from doctors_main where userid = '".$userid."')) as bt_view,
instr(useradd,(select usergroupid from doctors_main where userid = '".$userid."')) as bt_new,
instr(userDelete,(select usergroupid from doctors_main where userid = '".$userid."')) as bt_delete,
instr(useredit,(select usergroupid from doctors_main where userid = '".$userid."'))  as bt_edit
from menu where tname = '".$tablename."'";
    $result = $db->getOneRecord($str);
    if($result != NULL){
            $response["status"] = "success";
            $response["message"] = "";
            $response["bt_new"] = $result['bt_new'];
            $response["bt_view"] = $result['bt_view'];
            $response["bt_delete"] = $result['bt_delete'];
            $response["bt_edit"] = $result['bt_edit'];
            echoResponse(200, $response);
    }else{
        $response["status"] = "error";
        $response["message"] = "Утга авахад алдаа гарлаа!";
        echoResponse(201, $response);
    }
});
//
//getList  orderanalysis
//

$app->post('/getListmrOrderAnalysisPrint', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $where = $r->where;
    $paging = $r->paging;
    $orderby = $r->orderby;
     $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['id'];
     if($where == "")
    { $result = $db->getRecord("select if((SELECT id from mrlabrecord where regdate > o.date and ((o.test = 3 and isresHCV_DNA ='Y' ) or (o.test = 2 and isResHBV_DNA ='Y' ) or (o.test = 4 and isResHDV_DNA ='Y' ) ) and ubchtunid = u.id order by regdate limit 1) is not null, 0, (if((SELECT id from mrlabrecord where regdate > o.date and isResHDV_Ab = 'Y' and  ubchtunid = u.id and o.test = 1 order by regdate limit 1) is not null, 0, 1) )) as isresult , (SELECT HDV_Ab from mrlabrecord where regdate > o.date and isResHDV_Ab = 'Y' and  ubchtunid = u.id order by regdate limit 1) as result, u.id , (select count(1) from mrorderanalysis where reguser = $userid) totalcount, u.systemcode, u.lastname, u.firstname, u.rd, u.mobile, o.* from mrorderanalysis o left join ubchtun_main u on u.id = o.ubchtunid where o.reguser = $userid order by o.id DESC". $paging);
    } else {
      $result = $db->getRecord("select if((SELECT id from mrlabrecord where regdate > o.date and ((o.test = 3 and isresHCV_DNA ='Y' ) or (o.test = 2 and isResHBV_DNA ='Y' ) or (o.test = 4 and isResHDV_DNA ='Y' ) ) and ubchtunid = u.id order by regdate limit 1) is not null, 0, (if((SELECT id from mrlabrecord where regdate > o.date and isResHDV_Ab = 'Y' and  ubchtunid = u.id and o.test = 1 order by regdate limit 1) is not null, 0, 1) )) as isresult , (SELECT HDV_Ab from mrlabrecord where regdate > o.date and isResHDV_Ab = 'Y' and  ubchtunid = u.id order by regdate limit 1) as result, u.id , (select count(1) from mrorderanalysis where reguser = $userid) totalcount, u.systemcode, u.lastname, u.firstname, u.rd, u.mobile, o.* from mrorderanalysis o left join ubchtun_main u on u.id = o.ubchtunid where o.reguser = $userid and (upper(u.lastname) like upper('%$where%') or upper(u.firstname) like upper('%$where%') or upper(u.rd) like upper('%$where%') or upper(u.systemcode) like upper('%$where%')) order by o.id DESC");
    }
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';

          $response['totalcount'] = 0;
            $rows =  array();
            while($r =$result->fetch_assoc())
           {
            $rows[] = $r;
           }
           if(count($rows) > 0)
          {
            $response['totalcount'] = $rows[0]['totalcount'];
            $response['data'] = json_encode($rows);
          } else $response['data'] = null;
    }else {
            $response['status'] = "error";
            $response['message'] = 'Бүртгэлгүй байна.'.$labbarcode.$result;
        }
    echoResponse(200, $response);
});
//
//getList  mrlabrecord
//

$app->post('/getListmrlab', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $response = array();
    $db = new DbHandler();
    $where = $r->where;
    $paging = $r->paging;
    $orderby = $r->orderby;
    if($where != "")
    { $result = $db->getRecord("select (select count(1) from mrlabrecord) totalcount, u.lastname, u.firstname, u.rd, u.systemcode, ls.* from mrlabrecord ls inner join ubchtun_main u on u.id = ls.ubchtunid where upper(u.lastname) like upper('%$where%') or upper(u.firstname) like upper('%$where%') or upper(u.rd) like upper('%$where%') or upper(u.systemcode) like upper('%$where%') order by ls.id DESC". $paging);
    } else
    {
      $result = $db->getRecord("select (select count(1) from mrlabrecord) totalcount, u.lastname, u.firstname, u.rd, u.systemcode, ls.* from mrlabrecord ls inner join ubchtun_main u on u.id = ls.ubchtunid order by ls.id DESC". $paging);
    }
    if ($result != NULL)  {
        $response['status'] = "success";
        $response['message'] = '';

          $response['totalcount'] = 0;
            $rows =  array();
            while($r =$result->fetch_assoc())
           {
            $rows[] = $r;
           }
           if(count($rows) > 0)
          {
            $response['totalcount'] = $rows[0]['totalcount'];
            $response['data'] = json_encode($rows);
          } else $response['data'] = null;
    }else {
            $response['status'] = "error";
            $response['message'] = 'Бүртгэлгүй байна.'.$labbarcode.$result;
        }
    echoResponse(200, $response);
});
$app->post('/getList', function() use ($app) {
     $r = json_decode($app->request->getBody());
     $tablename = $r->tablename;
     $id = $r->id;
     $paging = $r->paging;
     $where = $r->where;
     $orderby = $r->orderby == "" ? " ORDER BY m.id desc " : $r->orderby;
     if($id != -1) $idname = $r->idname;
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];
//-------
  $selstr="";
  $joinstr="";
 $str = "select * from system_table where systemtablename = '".$tablename."' and listvisit = 'Y' order by sortid";
 $resultc = $db->getRecord($str);
  $rows=null;
 if($resultc != NULL){
            while($r =$resultc->fetch_assoc())
           {
           $rows[] = $r;
           }
           $arr_length = count($rows);
           for ($i=0; $i < $arr_length; $i++) {
             switch($rows[$i]['type'])
                {
                    case "city":
                    case "khoroo":
                    case "district":
                    case "combobox":
                        $selstr .= ", ".$rows[$i]['related'].$i.".".$rows[$i]['vfield']." ".$rows[$i]['vfield'].$rows[$i]['sortid'];
                        $joinstr .= " left join ".$rows[$i]['related']." ".$rows[$i]['related'].$i." on m.".$rows[$i]['field']." = ".$rows[$i]['related'].$i.".".$rows[$i]['rfield'];
                    break;
                }
            }
         }
//-------

   if($id != -1) { if($where == "") $str = "select (select count(*) from ".$tablename." where m.".$idname." = '".$id."') totalcount, m.* ".$selstr." from ".$tablename." m ".$joinstr." where m.".$idname." = '".$id."'".$orderby.$paging;
                   else $str = "select (select count(*) from ".$tablename." where m.".$idname." = '".$id."') totalcount, m.* ".$selstr." from ".$tablename." m ".$joinstr." ".$where." and m.".$idname." = '".$id."'".$orderby.$paging; }
   else $str = "select (select count(*) from ".$tablename.") totalcount, m.* ".$selstr." from ".$tablename." m".$joinstr." ".$where.$orderby. $paging;
    $result = $db->getRecord($str);
    if($result != NULL){
            $response["status"] = "success";
            $response["message"] = "";
            $response['totalcount'] = 0;
            $rows =  array();
            while($r =$result->fetch_assoc())
           {
            $rows[] = $r;
           }
           if(count($rows) > 0)
          {
            $response['totalcount'] = $rows[0]['totalcount'];
            $response['data'] = json_encode($rows);
          }
            echoResponse(200, $response);
    }else{
        $response["status"] = "error";
        $response["message"] = "Утга авахад алдаа гарлаа!";
        echoResponse(201, $response);
    }
});

$app->post('/getColumns', function() use ($app) {
     $r = json_decode($app->request->getBody());
    //verifyRequiredParams(array('email', 'name', 'password'),$r->customer);
    $tablename = $r->tablename;

    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];

    $str = "select case when type in ('combobox','city','district','khoroo') then CONCAT(vfield,sortid) else field end field, title as displayName
             from system_table where listvisit = 'Y' and systemtablename = '".$tablename."' order by sortid";
    $mrconstant = $db->getRecord($str);
    if($mrconstant != NULL){
            $response["status"] = "success";
            $response["message"] = "";
            $rows =  array();
            while($r =$mrconstant->fetch_assoc())
           {
            $rows[] = $r;
           }
           if(count($rows) > 0)
            $response['data'] = json_encode($rows);
            echoResponse(200, $response);
    }else{
        $response["status"] = "error";
        $response["message"] = "Утга авахад алдаа гарлаа!";
        echoResponse(201, $response);
    }
});

$app->post('/deleteOneRow', function() use ($app) {
     $r = json_decode($app->request->getBody());
    //verifyRequiredParams(array('email', 'name', 'password'),$r->customer);
    $tablename = $r->tablename;
    $id = $r->id;

    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];

    $str = "delete from ".$tablename." where id = ".$id;
    $result = $db->deleteRecord($str);
    if($result){
            $response["status"] = "success";
            $response["message"] = "Амжилттай устгалаа.";
            echoResponse(200, $response);
    }else{
        $response["status"] = "error";
        $response["message"] = "Устгахад алдаа гарлаа!";
        echoResponse(201, $response);
    }
});


//
//mrLabStorageSave
//

$app->post('/mrLabStorageSave', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $form = $r->params->form;
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];
    $barcode= $form->barcode;
    $positionid = $form->positionid;
    $type = substr($barcode,14,2);

$result = $db->getOneRecord("select count(1) pcnt, id from mrlabstorage where positionid = '$positionid'");
if ($result != NULL && (($result["pcnt"] == 0 && $form->mode == "new")  || ( $result["pcnt"] == 1 && $form->mode == "edit" && $result["id"] == $form->labstorageid) ))  {

$result = $db->getOneRecord("select count(1) cnt, id from mrlabstorage where barcode = '$barcode'");
if ($result != NULL && (($result["cnt"] == 0 && $form->mode == "new")  || ( $result["cnt"] == 1 && $form->mode == "edit" && $result["id"] == $form->labstorageid) ))  {//
$result = $db->getOneRecord("select * from mrlabrecord where labbarcode = substring('$barcode',1,14)");
if ($result != NULL)  {
        $ubchtunid = $result["ubchtunid"];
        $labrecordid = $result["id"];
        $labid = $result["labid"];

switch ($form->mode) {
    case "edit":
    $str = "update `mrlabstorage` set
                  `barcode` = '".$barcode."',
                  `ubchtunid` = '".$ubchtunid."',
                  `labrecordid` = '".$labrecordid."',
                  `amount` = '".$form->amount."',
                  `positionid` = '".$form->positionid."',
                  `fridge` = '".$form->fridge."',
                  `room` = '".$form->room."',
                  `floor` = '".$form->floor."',
                  `column` = '".$form->column."',
                  `row` = '".$form->row."',
                  `position` = '".$form->position."',
                  `type` = '".$type."',
                  `labid` = '".$labid."'
                  where `id` = ".$form->labstorageid."
               ";
        $labstorageid = $form->labstorageid;
        $result = $db->updateQuery($str);
    break;
    default:

                     $str = "insert into `mrlabstorage` (
                                  `ubchtunid`,
                                  `labrecordid`,
                                  `amount`,
                                  `positionid`,
                                  `barcode`,
                                  `fridge`,
                                  `room`,
                                  `floor`,
                                  `column`,
                                  `row`,
                                  `position`,
                                  `date`,
                                  `reguser` ,
                                  `regdate`,
                                  `labid`,
                                  `type`
                                )
                                values(
                                  ".$ubchtunid.",
                                  ".$labrecordid.",
                                  '".$form->amount."',
                                  '".$form->positionid."',
                                  '".$barcode."',
                                  '".$form->fridge."',
                                  '".$form->room."',
                                  '".$form->floor."',
                                  '".$form->column."',
                                  '".$form->row."',
                                  '".$form->position."',
                                  '".substr($form->date,0,10)."',
                                  '".$userid."',
                                  '".date("Y-m-d H:i:s")."',
                                  '".$labid."',
                                  '".$type."'
                                  )";
                $result = $db->insertQuery($str);
    break;
}
if($result != NULL)
{
 if($form->mode != "edit")
 {
  $labstorageid = $result;
 }
            $response["status"] = "success";
            $response["message"] = "Амжилттай хадгаллаа.";
            $response["labstorageid"] = $labstorageid;
            echoResponse(200, $response);
}
else{
        $response["status"] = "error";
        $response["message"] = "Хадгалахад алдаа гарлаа!";
        echoResponse(201, $response);
    }
}
else {
  $response["status"] = "error";
        $response["message"] = "Бүртгэл хийгдээгүй байна.";
        echoResponse(201, $response);
}
} else {
        $response["status"] = "error";
        $response["message"] = "Баркод давхардаж байна!";
        echoResponse(201, $response);
}
} else {
        $response["status"] = "error";
        $response["message"] = "Байрлал давхардаж байна!";
        echoResponse(201, $response);
}
});
//
//mrLabRecordSave
//

$app->post('/mrLabRecordSave', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $form = $r->params->form;
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['id'];
    $error = false;

      $ubchtunid = $form->ubchtunid;
      $datenow = date("Y-m-d H:i:s");
      $barcode = $form->labbarcode;
      $reguser = $userid;
      $regdate = date("Y-m-d H:i:s");


      $str_HBV = "";
      $str_HCV = "";
      $str_HDV = "";
      $str_anti_HDV = "";
      $str_SYSMEX = "";
      $str_Genotype = "";
      $str_VitD = "";
      $str_FER = "";


switch ($form->mode) {
    case "edit":
     $sourceid = $form->labrecordid;
    if($form->isHDV_Ab && $form->isPaidHDV_Ab)
   {
   $result = $db->getOneRecord("select max(positionid) positionid, isactive from mraptray where testtype = 'anti_HDV' group by isactive HAVING (max(positionid) < 45 and isactive = 0) or (isactive = 1) order by isactive");
    $positionid = $result['positionid']? $result['positionid'] + 1 : 1;
      $isactive = $result['isactive'] ? $result['isactive'] : 0;
      $testtype = 'anti_HDV';
   $result = $db->getOneRecord("SELECT count(1) cnt FROM mraptrayhist WHERE testtype = 'anti_HDV' and barcode = '$barcode' and sourceid = '$sourceid'");
    if(($result != null and $result["cnt"] > 0) || date(substr($form->date,0,10)) < date("2015-12-03")) $str_anti_HDV = "";
    else $str_anti_HDV = "insert into mraptray (positionid, isactive, testtype, date, ubchtunid, priority, barcode, reguser, regdate, sourcekey, sourceid) values($positionid, $isactive, '$testtype', '$datenow', $ubchtunid, 0, '$barcode', '$reguser', '$regdate' ,'mrlabrecord', '$sourceid');";
   }

   if($form->isHBV_DNA && $form->isPaidHBV_DNA)
   {
   $result = $db->getOneRecord("select max(positionid) positionid, isactive from mraptray where testtype = 'HBV' group by isactive HAVING (max(positionid) < 45 and isactive = 0) or (isactive = 1) order by isactive");
    $positionid = $result['positionid']? $result['positionid'] + 1 : 1;
      $isactive = $result['isactive'] ? $result['isactive'] : 0;
      $testtype = 'HBV';
   $result = $db->getOneRecord("SELECT count(1) cnt FROM mraptrayhist WHERE testtype = 'HBV' and barcode = '$barcode' and sourceid = '$sourceid'");
    if(($result != null and $result["cnt"] > 0) || date(substr($form->date,0,10)) < date("2015-12-03")) $str_HBV = "";
    else $str_HBV = "insert into mraptray (positionid, isactive, testtype, date, ubchtunid, priority, barcode, reguser, regdate, sourcekey, sourceid)
                values($positionid, $isactive, '$testtype', '$datenow', $ubchtunid, 0, '$barcode', '$reguser', '$regdate' ,'mrlabrecord', '$sourceid');";
   }
   if($form->isHCV_DNA && $form->isPaidHCV_DNA)
   {
   $result = $db->getOneRecord("select max(positionid) positionid, isactive from mraptray where testtype = 'HCV' group by isactive HAVING (max(positionid) < 95 and isactive = 0) or (isactive = 1) order by isactive");
      $positionid = $result['positionid']? $result['positionid'] + 1 : 1;
      $isactive = $result['isactive'] ? $result['isactive'] : 0;
      $testtype = 'HCV';
    $result = $db->getOneRecord("SELECT count(1) cnt FROM mraptrayhist WHERE testtype = 'HCV' and barcode = '$barcode' and sourceid = '$sourceid'");
    if(($result != null and $result["cnt"] > 0) || date(substr($form->date,0,10)) < date("2015-12-03")) $str_HCV = "";
    else
      $str_HCV = "insert into mraptray (positionid, isactive, testtype, date, ubchtunid, priority, barcode, reguser, regdate, sourcekey, sourceid)  
                values($positionid, $isactive, '$testtype', '$datenow', $ubchtunid, 0,'$barcode', '$reguser', '$regdate' , 'mrlabrecord', '$sourceid');";
   }
   if($form->isHDV_DNA && $form->isPaidHDV_DNA)
   {
   $result = $db->getOneRecord("select max(positionid) positionid, isactive from mraptray where testtype = 'HDV' group by isactive HAVING (max(positionid) < 45 and isactive = 0) or (isactive = 1) order by isactive");
      $positionid = $result['positionid']? $result['positionid'] + 1 : 1;
      $isactive = $result['isactive'] ? $result['isactive'] : 0;
      $testtype = 'HDV';
      $result = $db->getOneRecord("SELECT count(1) cnt FROM mraptrayhist WHERE testtype = 'HDV' and barcode = '$barcode' and sourceid = '$sourceid'");
    if(($result != null and $result["cnt"] > 0) || date(substr($form->date,0,10)) < date("2015-12-03")) $str_HDV = "";
    else $str_HDV = "insert into mraptray (positionid, isactive, testtype, date, ubchtunid, priority, barcode, reguser, regdate, sourcekey, sourceid)
                values($positionid, $isactive, '$testtype', '$datenow', $ubchtunid, 0, '$barcode', '$reguser', '$regdate', 'mrlabrecord', '$sourceid');";
   }
   if(($form->isHBsAgQ && $form->isPaidHBsAgQ) || ($form->isHBeAg && $form->isPaidHBeAg) || ($form->isaHBs && $form->isPaidaHBs) || ($form->isM2BPGI && $form->isPaidM2BPGI) || ($form->isAFP && $form->isPaidAFP) || ($form->isHCV_AbQ && $form->isPaidHCV_AbQ) || ($form->isTSH && $form->isPaidTSH) || ($form->isFT3 && $form->isPaidFT3) || ($form->isFT4 && $form->isPaidFT4))
   {
   $result = $db->getOneRecord("select max(positionid) positionid, isactive from mraptray where testtype = 'SYSMEX' group by isactive HAVING (max(positionid) < 45 and isactive = 0) or (isactive = 1) order by isactive");
      $positionid = $result['positionid']? $result['positionid'] + 1 : 1;
      $isactive = $result['isactive'] ? $result['isactive'] : 0;
      $testtype = 'SYSMEX';
      $testdetail = ($form->isHBsAgQ && $form->isPaidHBsAgQ ? "HBsAgQ": "") . ($form->isHBeAg && $form->isPaidHBeAg ? "HBeAg" : "") . ($form->isaHBs && $form->isPaidaHBs ? "aHBs" : "") . ($form->isM2BPGI && $form->isPaidM2BPGI ? "M2BPGI" : "") . ($form->isAFP && $form->isPaidAFP ? "AFP" : "") .   ($form->isHCV_AbQ && $form->isPaidHCV_AbQ ? "HCV_AbQ" : "") . ($form->isTSH && $form->isPaidTSH ? "TSH" : "") . ($form->isFT3 && $form->isPaidFT3 ? "FT3" : "") . ($form->isFT4 && $form->isPaidFT4 ? "FT4" : "");
    $result = $db->getOneRecord("SELECT count(1) cnt FROM mraptrayhist WHERE testtype = 'SYSMEX' and barcode = '$barcode' and sourceid = '$sourceid'");
    if(($result != null and $result["cnt"] > 0) || date(substr($form->date,0,10)) < date("2015-12-03 00:00:00")) $str_SYSMEX = "";
    else $str_SYSMEX = "insert into mraptray (positionid, isactive, testtype, date, ubchtunid, priority, barcode, testdetail, reguser, regdate, sourcekey, sourceid)
                values($positionid, $isactive, '$testtype', '$datenow', $ubchtunid, 0, '$barcode', '$testdetail','$reguser', '$regdate', 'mrlabrecord', '$sourceid');";
   }
   if($form->isGenotype && $form->isPaidGenotype)
   {
      $result = $db->getOneRecord("select max(positionid) positionid, isactive from mraptray where testtype = 'GENOTYPE' group by isactive HAVING (max(positionid) < 45 and isactive = 0) or (isactive = 1) order by isactive");
      $positionid = $result['positionid']? $result['positionid'] + 1 : 1;
      $isactive = $result['isactive'] ? $result['isactive'] : 0;
      $testtype = 'GENOTYPE';
    $result = $db->getOneRecord("SELECT count(1) cnt FROM mraptrayhist WHERE testtype = 'GENOTYPE' and barcode = '$barcode' and sourceid = '$sourceid'");
    if(($result != null and $result["cnt"] > 0)) $str_Genotype = "";
    else $str_Genotype = "insert into mraptray (positionid, isactive, testtype, date, ubchtunid, priority, barcode, reguser, regdate, sourcekey, sourceid)
                values($positionid, $isactive, '$testtype', '$datenow', $ubchtunid, 0, '$barcode', '$reguser', '$regdate', 'mrlabrecord', '$sourceid');";
   }

   if($form->isVitD && $form->isPaidVitD)
   {
      $result = $db->getOneRecord("select max(positionid) positionid, isactive from mraptray where testtype = 'VITD' group by isactive HAVING (max(positionid) < 45 and isactive = 0) or (isactive = 1) order by isactive");
      $positionid = $result['positionid']? $result['positionid'] + 1 : 1;
      $isactive = $result['isactive'] ? $result['isactive'] : 0;
      $testtype = 'VITD';
    $result = $db->getOneRecord("SELECT count(1) cnt FROM mraptrayhist WHERE testtype = 'VITD' and barcode = '$barcode' and sourceid = '$sourceid'");
    if(($result != null and $result["cnt"] > 0)) $str_VitD = "";
    else $str_VitD = "insert into mraptray (positionid, isactive, testtype, date, ubchtunid, priority, barcode, reguser, regdate, sourcekey, sourceid)
                values($positionid, $isactive, '$testtype', '$datenow', $ubchtunid, 0, '$barcode', '$reguser', '$regdate', 'mrlabrecord', '$sourceid');";
   }
   if($form->isFER && $form->isPaidFER)
   {
      $result = $db->getOneRecord("select max(positionid) positionid, isactive from mraptray where testtype = 'FER' group by isactive HAVING (max(positionid) < 45 and isactive = 0) or (isactive = 1) order by isactive");
      $positionid = $result['positionid']? $result['positionid'] + 1 : 1;
      $isactive = $result['isactive'] ? $result['isactive'] : 0;
      $testtype = 'FER';
    $result = $db->getOneRecord("SELECT count(1) cnt FROM mraptrayhist WHERE testtype = 'FER' and barcode = '$barcode' and sourceid = '$sourceid'");
    if(($result != null and $result["cnt"] > 0)) $str_FER = "";
    else $str_FER = "insert into mraptray (positionid, isactive, testtype, date, ubchtunid, priority, barcode, reguser, regdate, sourcekey, sourceid)
                values($positionid, $isactive, '$testtype', '$datenow', $ubchtunid, 0, '$barcode', '$reguser', '$regdate', 'mrlabrecord', '$sourceid');";
   }

    $str = "update `mrlabrecord` set
                 `isSecondDoctor` = '".($form->isSecondDoctor ? "Y" : "N")."',
                 `isDoctor` = '".($form->isDoctor ? "Y" : "N")."',
                 `isHBsAg` = '".($form->isHBsAg ? "Y" : "N")."',
                 `isHCV_Ab` = '".($form->isHCV_Ab ? "Y" : "N")."',
                 `isHIV` = '".($form->isHIV ? "Y" : "N")."',
                 `isSyphilis` = '".($form->isSyphilis ? "Y" : "N")."',
                 `isHDV_Ab` = '".($form->isHDV_Ab ? "Y" : "N")."',
                 `isHBV_DNA` = '".($form->isHBV_DNA ? "Y" : "N")."',
                 `isHCV_DNA` = '".($form->isHCV_DNA ? "Y" : "N")."',
                 `isHDV_DNA` = '".($form->isHDV_DNA ? "Y" : "N")."',
                 `isPaidDoctor` = '".($form->isPaidDoctor ? "Y" : "N")."',
                 `isPaidHBsAg` = '".($form->isPaidHBsAg ? "Y" : "N")."',
                 `isPaidHCV_Ab` = '".($form->isPaidHCV_Ab ? "Y" : "N")."',
                  `isPaidHIV` = '".($form->isPaidHIV ? "Y" : "N")."',
                 `isPaidSyphilis` = '".($form->isPaidSyphilis ? "Y" : "N")."',
                 `isPaidHDV_Ab` = '".($form->isPaidHDV_Ab ? "Y" : "N")."',
                 `isPaidHBV_DNA` = '".($form->isPaidHBV_DNA ? "Y" : "N")."',
                 `isPaidHCV_DNA` = '".($form->isPaidHCV_DNA ? "Y" : "N")."',
                 `isPaidHDV_DNA` = '".($form->isPaidHDV_DNA ? "Y" : "N")."',
                 `isResDoctor` = '".($form->isResDoctor ? "Y" : "N")."',
                 `isResHBsAg` = '".($form->isResHBsAg ? "Y" : "N")."',
                 `isResHCV_Ab` = '".($form->isResHCV_Ab ? "Y" : "N")."',
                 `isResHIV` = '".($form->isResHIV ? "Y" : "N")."',
                 `isResSyphilis` = '".($form->isResSyphilis ? "Y" : "N")."',
                 `isResHDV_Ab` = '".($form->isResHDV_Ab ? "Y" : "N")."',
                 `isResHBV_DNA` = '".($form->isResHBV_DNA ? "Y" : "N")."',
                 `isResHCV_DNA` = '".($form->isResHCV_DNA ? "Y" : "N")."',
                 `isResHDV_DNA` = '".($form->isResHDV_DNA ? "Y" : "N")."',

                 `isHBsAgQ` = '".($form->isHBsAgQ ? "Y" : "N")."',
                 `isHCV_AbQ` = '".($form->isHCV_AbQ ? "Y" : "N")."',
                 `isTSH` = '".($form->isTSH ? "Y" : "N")."',
                 `isFT3` = '".($form->isFT3 ? "Y" : "N")."',
                 `isFT4` = '".($form->isFT4 ? "Y" : "N")."',
                 `isHBeAg` = '".($form->isHBeAg ? "Y" : "N")."',
                 `isaHBs` = '".($form->isaHBs ? "Y" : "N")."',
                 `isM2BPGI` = '".($form->isM2BPGI ? "Y" : "N")."',
                 `isAFP` = '".($form->isAFP ? "Y" : "N")."',
                 `isVitD` = '".($form->isVitD ? "Y" : "N")."',
                  `isFER` = '".($form->isFER ? "Y" : "N")."',
                 `isFibroScan` = '".($form->isFibroScan ? "Y" : "N")."',
                 `isMDTConsultation` = '".($form->isMDTConsultation ? "Y" : "N")."',

                 `isPaidHBsAgQ` = '".($form->isPaidHBsAgQ ? "Y" : "N")."',
                 `isPaidHCV_AbQ` = '".($form->isPaidHCV_AbQ ? "Y" : "N")."',
                 `isPaidTSH` = '".($form->isPaidTSH ? "Y" : "N")."',
                 `isPaidFT3` = '".($form->isPaidFT3 ? "Y" : "N")."',
                 `isPaidFT4` = '".($form->isPaidFT4 ? "Y" : "N")."',
                 `isPaidHBeAg` = '".($form->isPaidHBeAg ? "Y" : "N")."',
                 `isPaidaHBs` = '".($form->isPaidaHBs ? "Y" : "N")."',
                 `isPaidM2BPGI` = '".($form->isPaidM2BPGI ? "Y" : "N")."',
                 `isPaidAFP` = '".($form->isPaidAFP ? "Y" : "N")."',
                 `isPaidVitD` = '".($form->isPaidVitD ? "Y" : "N")."',
                 `isPaidFER` = '".($form->isPaidFER ? "Y" : "N")."',
                 `isPaidFibroScan` = '".($form->isPaidFibroScan ? "Y" : "N")."',
                 `isPaidMDTConsultation` = '".($form->isPaidMDTConsultation ? "Y" : "N")."',

                 `isResHBsAgQ` = '".($form->isResHBsAgQ ? "Y" : "N")."',
                 `isResHCV_AbQ` = '".($form->isResHCV_AbQ ? "Y" : "N")."',
                 `isResTSH` = '".($form->isResTSH ? "Y" : "N")."',
                 `isResFT3` = '".($form->isResFT3 ? "Y" : "N")."',
                 `isResFT4` = '".($form->isResFT4 ? "Y" : "N")."',
                 `isResHBeAg` = '".($form->isResHBeAg ? "Y" : "N")."',
                 `isResaHBs` = '".($form->isResaHBs ? "Y" : "N")."',
                 `isResM2BPGI` = '".($form->isResM2BPGI ? "Y" : "N")."',
                 `isResAFP` = '".($form->isResAFP ? "Y" : "N")."',
                 `isResVitD` = '".($form->isResVitD ? "Y" : "N")."',
                 `isResFER` = '".($form->isResFER ? "Y" : "N")."',
                 `isResFibroScan` = '".($form->isResFibroScan ? "Y" : "N")."',
                 `isResMDTConsultation` = '".($form->isResMDTConsultation ? "Y" : "N")."',

                 `HBsAgQ` = '".$form->HBsAgQ."',
                 `HCV_AbQ` = '".$form->HCV_AbQ."',
                 `TSH` = '".$form->TSH."',
                 `FT3` = '".$form->FT3."',
                 `FT4` = '".$form->FT4."',
                 `HBeAg` = '".$form->HBeAg."',
                 `aHBs` = '".$form->aHBs."',
                 `M2BPGI` = '".$form->M2BPGI."',
                 `AFP` = '".$form->AFP."',
                 `VitD` = '".$form->VitD."',
                 `FER` = '".$form->FER."',
                 `FibroScan` = '".$form->FibroScan."',
                 `MDTConsultation` = '".$form->MDTConsultation."',

                 `isDiscFibroscan` = '".($form->isDiscFibroscan ? "Y" : "N")."',

                 `isGenotype` = '".($form->isGenotype ? "Y" : "N")."',
                 `Genotype` = '".$form->Genotype."',
                 `isPaidGenotype` = '".($form->isPaidGenotype ? "Y" : "N")."',
                 `isResGenotype` = '".($form->isResGenotype ? "Y" : "N")."',
                 `isBlood` = '".($form->isBlood ? "Y" : "N")."',
                 `Blood` = '".$form->Blood."',
                 `isPaidBlood` = '".($form->isPaidBlood ? "Y" : "N")."',
                 `isResBlood` = '".($form->isResBlood ? "Y" : "N")."',
                  `isCoagulo` = '".($form->isCoagulo ? "Y" : "N")."',
                 `Coagulo` = '".$form->Coagulo."',
                 `isPaidCoagulo` = '".($form->isPaidCoagulo ? "Y" : "N")."',
                 `isResCoagulo` = '".($form->isResCoagulo ? "Y" : "N")."',
                 `isBiohimi` = '".($form->isBiohimi ? "Y" : "N")."',
                 `Biohimi` = '".$form->Biohimi."',
                 `priceBiohimi` = '".$form->priceBiohimi."',
                 `isPaidBiohimi` = '".($form->isPaidBiohimi ? "Y" : "N")."',
                 `isResBiohimi` = '".($form->isResBiohimi ? "Y" : "N")."',

                 `cartamount` = '".$form->cartamount."',
                 `isCash` = '".($form->isCash ? "Y" : "N")."',
                 `cashamount` = '".$form->cashamount."',
                 `isCart` = '".($form->isCart ? "Y" : "N")."',
                 `isdiscount` = '".($form->isdiscount ? "Y" : "N")."',
                 `discountamount` = '".$form->discountamount."',
                 `isPaidEMD` = '".($form->isPaidEMD ? "Y" : "N")."',
                 `PaidEMD` = '".$form->PaidEMD."',
                 `isresearchdisc` = '".($form->isresearchdisc ? "Y" : "N")."',
                 `researchdiscamount` = '".$form->researchdiscamount."',
                 `isothercost` = '".($form->isothercost ? "Y" : "N")."',
                 `othercostamount` = '".$form->othercostamount."',

                 `labbarcode` = '".$form->labbarcode."',
                 `Doctor` = '".$form->Doctor."',
                 `date` = '".substr($form->date,0,10)."',
                 `HBsAg` = '".($form->HBsAg ? "Y" : "N")."',
                 `HCV_Ab` = '".($form->HCV_Ab ? "Y" : "N")."',
                  `HIV` = '".($form->HIV ? "Y" : "N")."',
                 `Syphilis` = '".($form->Syphilis ? "Y" : "N")."',
                 `HDV_Ab` = '".($form->HDV_Ab ? "Y" : "N")."',
                 `HBV_DNA_ul` = '".$form->HBV_DNA_ul."',
                 `HBV_DNA_s` = '".$form->HBV_DNA_s."',
                 `HCV_DNA` = '".$form->HCV_DNA."',
                 `HDV_DNA` = '".$form->HDV_DNA."',
                 `isPaidAll` = '".($form->isPaidAll ? "Y" : "N")."',
                 `remainPayment` = '".$form->remainPayment."',
                 `note` = '".$form->note."'
                  where `id` = ".$form->labrecordid."
               ";

        $labrecordid = $form->labrecordid;
        $db->startTransaction();

        $result = $db->updateQuery($str);
        if($result != NULL){
           $result = $db->deleteRecord("DELETE FROM mraptray WHERE testtype = 'HBV' and barcode = '$barcode' and sourceid = '$sourceid'"); if($result == NULL) { $error = true;}
           $result = $db->deleteRecord("DELETE FROM mraptray WHERE testtype = 'HCV' and barcode = '$barcode' and sourceid = '$sourceid'"); if($result == NULL) { $error = true;}
           $result = $db->deleteRecord("DELETE FROM mraptray WHERE testtype = 'HDV' and barcode = '$barcode' and sourceid = '$sourceid'"); if($result == NULL) { $error = true;}
           $result = $db->deleteRecord("DELETE FROM mraptray WHERE testtype = 'anti_HDV' and barcode = '$barcode' and sourceid = '$sourceid'"); if($result == NULL) { $error = true;}
           $result = $db->deleteRecord("DELETE FROM mraptray WHERE testtype = 'SYSMEX' and barcode = '$barcode' and sourceid = '$sourceid'"); if($result == NULL) { $error = true;}
           $result = $db->deleteRecord("DELETE FROM mraptray WHERE testtype = 'GENOTYPE' and barcode = '$barcode' and sourceid = '$sourceid'"); if($result == NULL) { $error = true;}
           $result = $db->deleteRecord("DELETE FROM mraptray WHERE testtype = 'VITD' and barcode = '$barcode' and sourceid = '$sourceid'"); if($result == NULL) { $error = true;}
           $result = $db->deleteRecord("DELETE FROM mraptray WHERE testtype = 'FER' and barcode = '$barcode' and sourceid = '$sourceid'"); if($result == NULL) { $error = true;}


          if($str_HBV != "") { $result = $db->insertQuery($str_HBV); if($result == NULL) { $error = true;}}
          if($str_HCV != "")  { $result = $db->insertQuery($str_HCV); if($result == NULL) { $error = true;}}
          if($str_HDV != "") { $result = $db->insertQuery($str_HDV); if($result == NULL) { $error = true;}}
          if($str_anti_HDV != "") { $result = $db->insertQuery($str_anti_HDV); if($result == NULL) { $error = true;}}
          if($str_SYSMEX != "") { $result = $db->insertQuery($str_SYSMEX); if($result == NULL) { $error = true;}}
          if($str_Genotype != "") { $result = $db->insertQuery($str_Genotype); if($result == NULL) { $error = true;}}
          if($str_VitD != "") { $result = $db->insertQuery($str_VitD); if($result == NULL) { $error = true;}}
          if($str_FER != "") { $result = $db->insertQuery($str_FER); if($result == NULL) { $error = true;}}


//-----update patient data--------
        if($form->isPaidBiohimi && $form->isBiohimi){
            $result = $db->getOneRecord("select COUNT(1) cnt from mrlabrecorddetail where labrecordid = $labrecordid");
            if($result["cnt"] > 0) {
           $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isGGT ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidGGT ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'GGT'"); if($result == NULL) { $error = true;}
           $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isTBIL ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidTBIL ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'TBIL'"); if($result == NULL) { $error = true;}
           $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isDBIL ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidDBIL ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'DBIL'"); if($result == NULL) { $error = true;}
           $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isALP ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidALP ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'ALP'"); if($result == NULL) { $error = true;}
           $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isALT ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidALT ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'ALT'"); if($result == NULL) { $error = true;}
           $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isAST ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidAST ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'AST'"); if($result == NULL) { $error = true;}
           $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isALB ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidALB ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'ALB'"); if($result == NULL) { $error = true;}
           $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isTP ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidTP ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'TP'"); if($result == NULL) { $error = true;}
           $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isLDH ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidLDH ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'LDH'"); if($result == NULL) { $error = true;}
           $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isCREA ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidCREA ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'CREA'"); if($result == NULL) { $error = true;}
           $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isUA ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidUA ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'UA'"); if($result == NULL) { $error = true;}
           $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isBUN ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidBUN ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'BUN'"); if($result == NULL) { $error = true;}
           $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isCa ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidCa ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'Ca'"); if($result == NULL) { $error = true;}
           $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isFe ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidFe ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'Fe'"); if($result == NULL) { $error = true;}
           $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isP ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidP ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'P'"); if($result == NULL) { $error = true;}
           $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isMg ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidMg ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'Mg'"); if($result == NULL) { $error = true;}
            $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isCl ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidCl ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'Cl'"); if($result == NULL) { $error = true;}
            $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isLipase ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidLipase ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'Lipase'"); if($result == NULL) { $error = true;}
            $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isGLU ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidGLU ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'GLU'"); if($result == NULL) { $error = true;}
            $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isHemoglobinA1c ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidHemoglobinA1c ? 'Y' : 'N')."', updated='$regdate', updatedby=$reguser where labrecordid = $labrecordid and code = 'HemoglobinA1c'"); if($result == NULL) { $error = true;}
            $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isAMY ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidAMY ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'AMY'"); if($result == NULL) { $error = true;}
            $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isLDL ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidLDL ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'LDL'"); if($result == NULL) { $error = true;}
            $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isHDL ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidHDL ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'HDL'"); if($result == NULL) { $error = true;}
            $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isTC ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidTC ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'TC'"); if($result == NULL) { $error = true;}
            $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isTryglycerides ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidTryglycerides ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'Tryglycerides'"); if($result == NULL) { $error = true;}
            $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isASO ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidASO ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'ASO'"); if($result == NULL) { $error = true;}
            $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isCRP ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidCRP ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'CRP'"); if($result == NULL) { $error = true;}
            $result = $db->updateQuery("update mrlabrecorddetail SET isChecked = '".($form->BiohimiD->isRF ? 'Y' : 'N')."', isPaid = '".($form->BiohimiD->isPaidRF ? 'Y' : 'N')."', updated = '$regdate', updatedby = $reguser where labrecordid = $labrecordid and code = 'RF'"); if($result == NULL) { $error = true;}
            }
            else {
              $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'GGT', 'GGT', '".($form->BiohimiD->isGGT ? 'Y' : 'N')."','".($form->BiohimiD->isPaidGGT ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
          $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'TBIL', 'TBIL', '".($form->BiohimiD->isTBIL ? 'Y' : 'N')."','".($form->BiohimiD->isPaidTBIL ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'DBIL', 'DBIL', '".($form->BiohimiD->isDBIL ? 'Y' : 'N')."','".($form->BiohimiD->isPaidDBIL ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'ALP', 'ALP', '".($form->BiohimiD->isALP ? 'Y' : 'N')."','".($form->BiohimiD->isPaidALP ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'ALT', 'ALT', '".($form->BiohimiD->isALT ? 'Y' : 'N')."','".($form->BiohimiD->isPaidALT ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'AST', 'AST', '".($form->BiohimiD->isAST ? 'Y' : 'N')."','".($form->BiohimiD->isPaidAST ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'ALB', 'ALB', '".($form->BiohimiD->isALB ? 'Y' : 'N')."','".($form->BiohimiD->isPaidALB ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'Total Protein', 'TP', '".($form->BiohimiD->isTP ? 'Y' : 'N')."','".($form->BiohimiD->isPaidTP ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'LDH', 'LDH', '".($form->BiohimiD->isLDH ? 'Y' : 'N')."','".($form->BiohimiD->isPaidLDH ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'CREA', 'CREA', '".($form->BiohimiD->isCREA ? 'Y' : 'N')."','".($form->BiohimiD->isPaidCREA ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'UA', 'UA', '".($form->BiohimiD->isUA ? 'Y' : 'N')."','".($form->BiohimiD->isPaidUA ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'BUN', 'BUN', '".($form->BiohimiD->isBUN ? 'Y' : 'N')."','".($form->BiohimiD->isPaidBUN ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'Ca', 'Ca', '".($form->BiohimiD->isCa ? 'Y' : 'N')."','".($form->BiohimiD->isPaidCa ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'Fe', 'Fe', '".($form->BiohimiD->isFe ? 'Y' : 'N')."','".($form->BiohimiD->isPaidFe ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'P', 'P', '".($form->BiohimiD->isP ? 'Y' : 'N')."','".($form->BiohimiD->isPaidP ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'Mg', 'Mg', '".($form->BiohimiD->isMg ? 'Y' : 'N')."','".($form->BiohimiD->isPaidMg ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'Cl', 'Cl', '".($form->BiohimiD->isCl ? 'Y' : 'N')."','".($form->BiohimiD->isPaidCl ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'Lipase', 'Lipase', '".($form->BiohimiD->isLipase ? 'Y' : 'N')."','".($form->BiohimiD->isPaidLipase ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'GLUCOSE', 'GLU', '".($form->BiohimiD->isGLU ? 'Y' : 'N')."','".($form->BiohimiD->isPaidGLU ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'HemoglobinA1c', 'HemoglobinA1c', '".($form->BiohimiD->isHemoglobinA1c ? 'Y' : 'N')."','".($form->BiohimiD->isPaidHemoglobinA1c ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'AMYLASE', 'AMY', '".($form->BiohimiD->isAMY ? 'Y' : 'N')."','".($form->BiohimiD->isPaidAMY ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'LDL', 'LDL', '".($form->BiohimiD->isLDL ? 'Y' : 'N')."','".($form->BiohimiD->isPaidLDL ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'HDL', 'HDL', '".($form->BiohimiD->isHDL ? 'Y' : 'N')."','".($form->BiohimiD->isPaidHDL ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'TC', 'TC', '".($form->BiohimiD->isTC ? 'Y' : 'N')."','".($form->BiohimiD->isPaidTC ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'Tryglycerides', 'Tryglycerides', '".($form->BiohimiD->isTryglycerides ? 'Y' : 'N')."','".($form->BiohimiD->isPaidTryglycerides ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'ASO', 'ASO', '".($form->BiohimiD->isASO ? 'Y' : 'N')."','".($form->BiohimiD->isPaidASO ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'CRP', 'CRP', '".($form->BiohimiD->isCRP ? 'Y' : 'N')."','".($form->BiohimiD->isPaidCRP ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'RF', 'RF', '".($form->BiohimiD->isRF ? 'Y' : 'N')."','".($form->BiohimiD->isPaidRF ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            }
          }

         if($form->isResHBsAg || $form->isResHCV_Ab || $form->isResHDV_Ab){
         $ups = "update `sh_hepatittest`
                    set `HBsAg` ='".($form->isResHBsAg && $form->HBsAg ? "Y" : ($form->isResHBsAg && !$form->HBsAg ? "N" : ""))."',

                        `anti_HCV` = '".($form->isResHCV_Ab && $form->HCV_Ab ? "Y" : ($form->isResHCV_Ab && !$form->HCV_Ab ? "N" : ""))."',

                        `anti_HDV` = '".($form->isResHDV_Ab && $form->HDV_Ab ? "Y" : ($form->isResHDV_Ab && $form->HDV_Ab ? "N" : ""))."'

                        where ubchtunid = ".$ubchtunid." and sourcekey = 'mrlabrecord' and sourceid = '".$form->labrecordid."'";
         $result = $db->updateQuery($ups); if($result == NULL) { $error = true;}
            }
         if($form->isResHIV){
         $ups = "update `sh_other`
                    set `result` ='".($form->isResHIV && $form->HIV ? "Y" : ($form->isResHIV && !$form->HIV ? "N" : ""))."'
                        where ubchtunid = ".$ubchtunid." and sourcekey = 'mrlabrecord' and sourceid = '".$form->labrecordid."'";
         $result = $db->updateQuery($ups); if($result == NULL) { $error = true;}
            }
         if($form->isResSyphilis){
         $ups = "update `sh_other`
                    set `result` ='".($form->isResSyphilis && $form->Syphilis ? "Y" : ($form->isResSyphilis && !$form->Syphilis ? "N" : ""))."'
                        where ubchtunid = ".$ubchtunid." and sourcekey = 'mrlabrecord' and sourceid = '".$form->labrecordid."'";
         $result = $db->updateQuery($ups); if($result == NULL) { $error = true;}
            }
        if($form->isResHCV_DNA || $form->isResHBV_DNA || $form->isResHDV_DNA){
         $ups = "update `sh_nuklein`
                    set `HCV_RNA` = '".($form->isResHCV_DNA ? $form->HCV_DNA : "")."',
                        `HBV_DNA` = '".($form->isResHBV_DNA ? $form->HBV_DNA_ul: "")."',
                        `HDV_RNA` = '".($form->isResHDV_DNA ? $form->HDV_DNA : "")."'
                        where ubchtunid = ".$ubchtunid." and sourcekey = 'mrlabrecord' and sourceid = '".$form->labrecordid."'";

         $result = $db->updateQuery($ups); if($result == NULL) { $error = true;}
            }

        if($form->isResHBsAgQ || $form->isResHBeAg || $form->isResAFP || $form->isResVitD || $form->isResM2BPGI || $form->isResaHBs || $form->isResGenotype || $form->isResHCV_AbQ || $form->isResTSH || $form->isResFT3 || $form->isResFT4){
         $ups = "update `sh_sismeks`
                    set `HBsAgQ` = '".($form->isResHBsAgQ ? $form->HBsAgQ : "")."',
                        `anti_HCV` = '".($form->isResHCV_AbQ  ? $form->HCV_AbQ  : "")."',
                        `TSH` = '".($form->isResTSH ? $form->TSH : "")."',
                        `FreeT3` = '".($form->isResFT3 ? $form->FT3 : "")."',
                        `FreeT4` = '".($form->isResFT4 ? $form->FT4 : "")."',
                        `HBeAg` = '".($form->isResHBeAg ? $form->HBeAg : "")."',
                        `AFP` = '".($form->isResAFP ? $form->AFP : "")."',
                        `VitD` = '".($form->isResVitD ? $form->VitD : "")."',
                        `M2BPGi` = '".($form->isResM2BPGI ? $form->M2BPGI : "")."',
                        `Genotype` = '".($form->isResGenotype ? $form->Genotype : "")."',
                        `anti_HBs` = '".($form->isResaHBs ? $form->aHBs : "")."'
                        where ubchtunid = ".$ubchtunid." and sourcekey = 'mrlabrecord' and sourceid = '".$form->labrecordid."'";

         $result = $db->updateQuery($ups); if($result == NULL) { $error = true;}
            }

            if($form->isResFER)  $result = $db->updateQuery("update sh_biohimi set FER = ".($form->isResFER ? $form->FER : "")." where ubchtunid = ".$ubchtunid." and sourcekey = 'mrlabrecord' and sourceid = '".$form->labrecordid."'"); if($result == NULL) { $error = true;}
//-----update patient data--------

              if($error == false) $db->commitTransaction();
                 else $db->rollbackTransaction();
          } else $db->rollbackTransaction();

    break;
    case "labedit":
   if(property_exists($form, 'HBsAgQ')){
    $str = "update `mrlabrecord` set
                 `isResDoctor` = '".($form->isResDoctor ? "Y" : "N")."',
                 `isResHBsAg` = '".($form->isResHBsAg ? "Y" : "N")."',
                 `isResHCV_Ab` = '".($form->isResHCV_Ab ? "Y" : "N")."',
                 `isResHIV` = '".($form->isResHIV ? "Y" : "N")."',
                 `isResSyphilis` = '".($form->isResSyphilis ? "Y" : "N")."',
                 `isResHDV_Ab` = '".($form->isResHDV_Ab ? "Y" : "N")."',
                 `isResHBV_DNA` = '".($form->isResHBV_DNA ? "Y" : "N")."',
                 `isResHCV_DNA` = '".($form->isResHCV_DNA ? "Y" : "N")."',
                 `isResHDV_DNA` = '".($form->isResHDV_DNA ? "Y" : "N")."',

                 `isResHBsAgQ` = '".($form->isResHBsAgQ ? "Y" : "N")."',
                 `isResHCV_AbQ` = '".($form->isResHCV_AbQ ? "Y" : "N")."',
                 `isResTSH` = '".($form->isResTSH ? "Y" : "N")."',
                 `isResFT3` = '".($form->isResFT3 ? "Y" : "N")."',
                 `isResFT4` = '".($form->isResFT4 ? "Y" : "N")."',
                 `isResHBeAg` = '".($form->isResHBeAg ? "Y" : "N")."',
                 `isResaHBs` = '".($form->isResaHBs ? "Y" : "N")."',
                 `isResM2BPGI` = '".($form->isResM2BPGI ? "Y" : "N")."',
                 `isResAFP` = '".($form->isResAFP ? "Y" : "N")."',
                  `isResVitD` = '".($form->isResVitD ? "Y" : "N")."',
                  `isResFER` = '".($form->isResFER ? "Y" : "N")."',
                 `isResGenotype` = '".($form->isResGenotype ? "Y" : "N")."',

                 `HBsAgQ` = '".$form->HBsAgQ."',
                 `HCV_AbQ` = '".$form->HCV_AbQ."',
                 `TSH` = '".$form->TSH."',
                 `FT3` = '".$form->FT3."',
                 `FT4` = '".$form->FT4."',
                 `HBeAg` = '".$form->HBeAg."',
                 `aHBs` = '".$form->aHBs."',
                 `M2BPGI` = '".$form->M2BPGI."',
                 `AFP` = '".$form->AFP."',
                 `VitD` = '".$form->VitD."',
                 `FER` = '".$form->FER."',
                 `Genotype` = '".$form->Genotype."',

                 `labbarcode` = '".$form->labbarcode."',
                 `HBsAg` = '".($form->HBsAg ? "Y" : "N")."',
                 `HCV_Ab` = '".($form->HCV_Ab ? "Y" : "N")."',
                 `HIV` = '".($form->HIV ? "Y" : "N")."',
                 `Syphilis` = '".($form->Syphilis ? "Y" : "N")."',
                 `HDV_Ab` = '".($form->HDV_Ab ? "Y" : "N")."',
                 `HBV_DNA_ul` = '".$form->HBV_DNA_ul."',
                 `HBV_DNA_s` = '".$form->HBV_DNA_s."',
                 `HCV_DNA` = '".$form->HCV_DNA."',
                 `HDV_DNA` = '".$form->HDV_DNA."',
                 `note` = '".$form->note."'
                  where `id` = ".$form->labrecordid."
               ";
             }
             else {
              $str = "update `mrlabrecord` set
                 `isResDoctor` = '".($form->isResDoctor ? "Y" : "N")."',
                 `isResHBsAg` = '".($form->isResHBsAg ? "Y" : "N")."',
                 `isResHCV_Ab` = '".($form->isResHCV_Ab ? "Y" : "N")."',
                 `isResHDV_Ab` = '".($form->isResHDV_Ab ? "Y" : "N")."',
                 `isResHBV_DNA` = '".($form->isResHBV_DNA ? "Y" : "N")."',
                 `isResHCV_DNA` = '".($form->isResHCV_DNA ? "Y" : "N")."',
                 `isResHDV_DNA` = '".($form->isResHDV_DNA ? "Y" : "N")."',
                 `labbarcode` = '".$form->labbarcode."',
                 `HBsAg` = '".($form->HBsAg ? "Y" : "N")."',
                 `HCV_Ab` = '".($form->HCV_Ab ? "Y" : "N")."',
                 `HDV_Ab` = '".($form->HDV_Ab ? "Y" : "N")."',
                 `HBV_DNA_ul` = '".$form->HBV_DNA_ul."',
                 `HBV_DNA_s` = '".$form->HBV_DNA_s."',
                 `HCV_DNA` = '".$form->HCV_DNA."',
                 `HDV_DNA` = '".$form->HDV_DNA."',
                 `note` = '".$form->note."'
                  where `id` = ".$form->labrecordid."
               ";
             }

$labrecordid = $form->labrecordid;
$db->startTransaction();
$result = $db->updateQuery($str);
if($result != NULL){

if($form->isResHDV_Ab ||  $form->isResHBV_DNA || $form->isResHCV_DNA || $form->isResHDV_DNA ||  $form->isResHBsAgQ || $form->isResHBeAg || $form->isResaHBs || $form->isResM2BPGI || $form->isResAFP || $form->isResVitD || $form->isResFER || $form->isResGenotype || $form->isResBlood || $form->isResBiohimi || $form->isResHCV_AbQ || $form->isResTSH || $form->isResFT3 || $form->isResFT4 ){
  $result = $db->getOneRecord("select u.* from ubchtun_main u where u.id = $ubchtunid");
  if($result != NULL)
  {
    if($result["tpass"] == null or $result["tpass"] == "")
    {
      $result["tpass"] = rand(100000,999999);
      $uptpass = "UPDATE ubchtun_main SET tpass = '".$result["tpass"]."' WHERE id =  ".$ubchtunid;
      $u_result = $db->updateQuery($uptpass); if($u_result == NULL) { $error = true;}
    }
    if(!$error){
     $lmsg = "Login:" . $result["systemcode"]. " Pass:".$result["tpass"];
     $rmsg = ($form->isResHDV_Ab && !$form->isMsgHDV_Ab ? "anti HDV," : "") . ($form->isResHBV_DNA && !$form->isMsgHBV_DNA ? "HBV," : "") . ($form->isResHCV_DNA && !$form->isMsgHCV_DNA ? "HCV," : ""). ($form->isResHDV_DNA && !$form->isMsgHDV_DNA ? "HDV," : ""). ($form->isResHBsAgQ && !$form->isMsgHBsAgQ ? "Quant HBsAg," : ""). ($form->isResHBeAg && !$form->isMsgHBeAg ? "HBeAg," : ""). ($form->isResaHBs && !$form->isMsgaHBs ? "aHBs," : ""). ($form->isResM2BPGI && !$form->isMsgM2BPGI ? "M2BPGI," : ""). ($form->isResAFP && !$form->isMsgAFP ? "AFP," : ""). ($form->isResVitD && !$form->isMsgVitD ? "VitD," : "") . ($form->isResFER && !$form->isMsgFER ? "Ferritin," : ""). ($form->isResGenotype && !$form->isMsgGenotype ? "Genotype," : ""). ($form->isResBlood && !$form->isMsgBlood ? "Hematology," : ""). ($form->isResBiohimi && !$form->isMsgBiohimi ? "Biochemistry," : "") . ($form->isResHCV_AbQ && !$form->isMsgHCV_AbQ ? "HCV_AbQ," : "") . ($form->isResTSH && !$form->isMsgTSH ? "TSH," : "") . ($form->isResFT3 && !$form->isMsgFT3 ? "FT3," : "") . ($form->isResFT4 && !$form->isMsgFT4 ? "FT4" : "");

       if($db->sendMSG($result["mobile"], "Elegnii Tuv ".$rmsg." shinjilgeenii hariu garlaa. burtgel.eleg.mn ".$lmsg, $userid))
       {
            $qmsg = "UPDATE mrlabrecord SET isMsgHDV_Ab = '".($form->isResHDV_Ab ? "Y" : "N")."', isMsgFT4 = '".($form->isResFT4 ? "Y" : "N")."',  isMsgFT3 = '".($form->isResFT3 ? "Y" : "N")."',  isMsgTSH = '".($form->isResTSH ? "Y" : "N")."', isMsgHCV_AbQ = '".($form->isResHCV_AbQ ? "Y" : "N")."', isMsgHBV_DNA = '".($form->isResHBV_DNA ? "Y" : "N")."', isMsgHCV_DNA = '".($form->isResHCV_DNA ? "Y" : "N")."', isMsgHDV_DNA = '".($form->isResHDV_DNA ? "Y" : "N")."', isMsgHBsAgQ = '".($form->isResHBsAgQ ? "Y" : "N")."', isMsgHBeAg = '".($form->isResHBeAg ? "Y" : "N")."', isMsgaHBs = '".($form->isResaHBs ? "Y" : "N")."', isMsgM2BPGI = '".($form->isResM2BPGI ? "Y" : "N")."', isMsgAFP = '".($form->isResAFP ? "Y" : "N")."', isMsgVitD = '".($form->isResVitD ? "Y" : "N")."', isMsgFER = '".($form->isResFER ? "Y" : "N")."', isMsgGenotype = '".($form->isResGenotype ? "Y" : "N")."', isMsgBlood = '".($form->isResBlood ? "Y" : "N")."', isMsgBiohimi = '".($form->isResBiohimi ? "Y" : "N")."' WHERE id = '".$form->labrecordid."'";
            $result = $db->updateQuery($qmsg); if($result == NULL) { $error = true;}
       }
     }
  }
 }

 //-----update patient data--------
         if($form->isResHBsAg || $form->isResHCV_Ab || $form->isResHDV_Ab){
         $ups = "update `sh_hepatittest`
                    set `HBsAg` ='".($form->isResHBsAg && $form->HBsAg ? "Y" : ($form->isResHBsAg && !$form->HBsAg ? "N" : ""))."',

                        `anti_HCV` = '".($form->isResHCV_Ab && $form->HCV_Ab ? "Y" : ($form->isResHCV_Ab && !$form->HCV_Ab ? "N" : ""))."',

                        `anti_HDV` = '".($form->isResHDV_Ab && $form->HDV_Ab ? "Y" : ($form->isResHDV_Ab && !$form->HDV_Ab ? "N" : ""))."'

                        where ubchtunid = ".$ubchtunid." and sourcekey = 'mrlabrecord' and sourceid = '".$form->labrecordid."'";
         $result = $db->updateQuery($ups); if($result == NULL) { $error = true;}
            }
            if($form->isResHIV){
         $ups = "update `sh_other`
                    set `result` ='".($form->isResHIV && $form->HIV ? "Y" : ($form->isResHIV && !$form->HIV ? "N" : ""))."'
                        where ubchtunid = ".$ubchtunid." and sourcekey = 'mrlabrecord' and sourceid = '".$form->labrecordid."'";
         $result = $db->updateQuery($ups); if($result == NULL) { $error = true;}
            }
         if($form->isResSyphilis){
         $ups = "update `sh_other`
                    set `result` ='".($form->isResSyphilis && $form->Syphilis ? "Y" : ($form->isResSyphilis && !$form->Syphilis ? "N" : ""))."'
                        where ubchtunid = ".$ubchtunid." and sourcekey = 'mrlabrecord' and sourceid = '".$form->labrecordid."'";
         $result = $db->updateQuery($ups); if($result == NULL) { $error = true;}
            }
        if($form->isResHCV_DNA || $form->isResHBV_DNA || $form->isResHDV_DNA){
         $ups = "update `sh_nuklein`
                    set `HCV_RNA` = '".($form->isResHCV_DNA ? $form->HCV_DNA : "")."',
                        `HBV_DNA` = '".($form->isResHBV_DNA ? $form->HBV_DNA_ul: "")."',
                        `HDV_RNA` = '".($form->isResHDV_DNA ? $form->HDV_DNA : "")."'
                        where ubchtunid = ".$ubchtunid." and sourcekey = 'mrlabrecord' and sourceid = '".$form->labrecordid."'";

         $result = $db->updateQuery($ups); if($result == NULL) { $error = true;}
            }
      if($form->isResHBsAgQ || $form->isResHBeAg || $form->isResAFP || $form->isResVitD || $form->isResM2BPGI || $form->isResaHBs || $form->isResGenotype || $form->isResHCV_AbQ || $form->isResTSH || $form->isResFT3 || $form->isResFT4){
         $ups = "update `sh_sismeks`
                    set `HBsAgQ` = '".($form->isResHBsAgQ ? $form->HBsAgQ : "")."',
                        `anti_HCV` = '".($form->isResHCV_AbQ  ? $form->HCV_AbQ : "")."',
                        `TSH` = '".($form->isResTSH ? $form->TSH : "")."',
                        `FreeT3` = '".($form->isResFT3 ? $form->FT3 : "")."',
                        `FreeT4` = '".($form->isResFT4 ? $form->FT4 : "")."',
                        `HBeAg` = '".($form->isResHBeAg ? $form->HBeAg : "")."',
                        `AFP` = '".($form->isResAFP ? $form->AFP : "")."',
                        `VitD` = '".($form->isResVitD ? $form->VitD : "")."',
                        `M2BPGi` = '".($form->isResM2BPGI ? $form->M2BPGI : "")."',
                        `Genotype` = '".($form->isResGenotype ? $form->Genotype : "")."',
                        `anti_HBs` = '".($form->isResaHBs ? $form->aHBs : "")."'
                        where ubchtunid = ".$ubchtunid." and sourcekey = 'mrlabrecord' and sourceid = '".$form->labrecordid."'";

         $result = $db->updateQuery($ups); if($result == NULL) { $error = true;}
            }
      if($form->isResFER)  $result = $db->updateQuery("update sh_biohimi set FER = ".($form->isResFER ? $form->FER : "")." where ubchtunid = ".$ubchtunid." and sourcekey = 'mrlabrecord' and sourceid = '".$form->labrecordid."'"); if($result == NULL) { $error = true;}
//-----update patient data--------

       if($error == false) $db->commitTransaction();
         else $db->rollbackTransaction();
  } else {  $db->rollbackTransaction();  $error = true;}

    break;
    default:


     $str = "insert into `mrlabrecord` (  `id`,
                  `ubchtunid`,

                  `isSecondDoctor`,
                  `isDoctor`,
                  `isHBsAg`,
                  `isHCV_Ab`,
                  `isHIV`,
                  `isSyphilis`,
                  `isHDV_Ab`,
                  `isHBV_DNA`,
                  `isHCV_DNA`,
                   `isHDV_DNA`,
                  `isPaidDoctor`,
                  `isPaidHBsAg`,
                  `isPaidHCV_Ab`,
                  `isPaidHIV`,
                  `isPaidSyphilis`,
                  `isPaidHDV_Ab`,
                  `isPaidHBV_DNA`,
                  `isPaidHCV_DNA`,
                   `isPaidHDV_DNA`,
                  `isResDoctor`,
                  `isResHBsAg`,
                  `isResHCV_Ab`,
                  `isResHIV`,
                  `isResSyphilis`,
                  `isResHDV_Ab`,
                  `isResHBV_DNA`,
                  `isResHCV_DNA`,
                  `isResHDV_DNA`,
                  `Doctor`,
                  `HBsAg`,
                  `HCV_Ab`,
                  `HIV`,
                  `Syphilis`,
                  `HDV_Ab`,
                  `HBV_DNA_ul`,
                  `HBV_DNA_s`,
                  `HCV_DNA`,
                  `HDV_DNA`,
                  `HBsAgQ`,
                  `HCV_AbQ`,
                  `TSH`,
                  `FT3`,
                  `FT4`,
                  `HBeAg`,
                  `aHBs`,
                  `M2BPGI`,
                  `AFP`,
                  `FibroScan`,
                  `MDTConsultation`,
                  `isHBsAgQ`,
                  `isHCV_AbQ`,
                  `isTSH`,
                  `isFT3`,
                  `isFT4`,
                  `isHBeAg`,
                  `isaHBs`,
                  `isM2BPGI`,
                  `isAFP`,
                  `isFibroScan`,
                  `isMDTConsultation`,
                  `isPaidHBsAgQ`,
                  `isPaidHCV_AbQ`,
                  `isPaidTSH`,
                  `isPaidFT3`,
                  `isPaidFT4`,
                  `isPaidHBeAg`,
                  `isPaidaHBs`,
                  `isPaidM2BPGI`,
                  `isPaidAFP`,
                  `isPaidFibroScan`,
                  `isPaidMDTConsultation`,
                  `isResHBsAgQ`,
                  `isResHCV_AbQ`,
                  `isResTSH`,
                  `isResFT3`,
                  `isResFT4`,
                  `isResHBeAg`,
                  `isResaHBs`,
                  `isResM2BPGI`,
                  `isResAFP`,
                  `isResFibroScan`,
                  `isResMDTConsultation`,
                  `labbarcode`,
                  `date`,
                  `reguser` ,
                  `regdate`,
                  `note`,
                  `labid`,
                  `isPaidAll`,
                  `remainPayment`,
                  `isDiscFibroscan`,

                  `cartamount`,
                  `isCash`,
                  `isCart`,
                  `cashamount`,
                  `isdiscount`,
                  `discountamount`,
                  `isPaidEMD`,
                  `PaidEMD`,
                  `isresearchdisc`,
                  `researchdiscamount`,
                  `isothercost`,
                  `othercostamount`,
                  `isGenotype`,
                  `Genotype`,
                  `isPaidGenotype`,
                  `isResGenotype`,
                  `isVitD`,
                  `VitD`,
                  `isPaidVitD`,
                  `isResVitD`,
                  `isFER`,
                  `FER`,
                  `isPaidFER`,
                  `isResFER`,
                  `isCoagulo`,
                  `Coagulo`,
                  `isPaidCoagulo`,
                  `isResCoagulo`,
                  `isBlood`,
                  `Blood`,
                  `isPaidBlood`,
                  `isResBlood`,
                  `isBiohimi`,
                  `Biohimi`,
                  `isPaidBiohimi`,
                  `isResBiohimi`,
                  `priceBiohimi`
                )
                values(".$form->labrecordid.",
                  ".$form->ubchtunid.",
                  '".($form->isSecondDoctor ? "Y" : "N")."',
                  '".($form->isDoctor ? "Y" : "N")."',
                  '".($form->isHBsAg ? "Y" : "N")."',
                  '".($form->isHCV_Ab  ? "Y" : "N")."',
                  '".($form->isHIV ? "Y" : "N")."',
                  '".($form->isSyphilis  ? "Y" : "N")."',
                  '".($form->isHDV_Ab ? "Y" : "N")."',
                  '".($form->isHBV_DNA ? "Y" : "N")."',
                  '".($form->isHCV_DNA ? "Y" : "N")."',
                  '".($form->isHDV_DNA ? "Y" : "N")."',
                  '".($form->isPaidDoctor ? "Y" : "N")."',
                  '".($form->isPaidHBsAg ? "Y" : "N")."',
                  '".($form->isPaidHCV_Ab  ? "Y" : "N")."',
                  '".($form->isPaidHIV ? "Y" : "N")."',
                  '".($form->isPaidSyphilis  ? "Y" : "N")."',
                  '".($form->isPaidHDV_Ab ? "Y" : "N")."',
                  '".($form->isPaidHBV_DNA ? "Y" : "N")."',
                  '".($form->isPaidHCV_DNA ? "Y" : "N")."',
                  '".($form->isPaidHDV_DNA ? "Y" : "N")."',
                  '".($form->isResDoctor ? "Y" : "N")."',
                  '".($form->isResHBsAg ? "Y" : "N")."',
                  '".($form->isResHCV_Ab  ? "Y" : "N")."',
                  '".($form->isResHIV ? "Y" : "N")."',
                  '".($form->isResSyphilis  ? "Y" : "N")."',
                  '".($form->isResHDV_Ab ? "Y" : "N")."',
                  '".($form->isResHBV_DNA ? "Y" : "N")."',
                  '".($form->isResHCV_DNA ? "Y" : "N")."',
                  '".($form->isResHDV_DNA ? "Y" : "N")."',
                  '".$form->Doctor."',
                  '".($form->HBsAg ? "Y" : "N")."',
                  '".($form->HCV_Ab ? "Y" : "N")."',
                  '".($form->HIV ? "Y" : "N")."',
                  '".($form->Syphilis ? "Y" : "N")."',
                  '".($form->HDV_Ab ? "Y" : "N")."',
                  '".$form->HBV_DNA_ul."',
                  '".$form->HBV_DNA_s."',
                  '".$form->HCV_DNA."',
                  '".$form->HDV_DNA."',

                  '".$form->HBsAgQ."',
                  '".$form->HCV_AbQ."',
                  '".$form->TSH."',
                  '".$form->FT3."',
                  '".$form->FT4."',
                  '".$form->HBeAg."',
                  '".$form->aHBs."',
                  '".$form->M2BPGI."',
                   '".$form->AFP."',
                   '".$form->FibroScan."',
                   '".$form->MDTConsultation."',

                   '".($form->isHBsAgQ ? "Y" : "N")."',
                   '".($form->isHCV_AbQ ? "Y" : "N")."',
                   '".($form->isTSH ? "Y" : "N")."',
                   '".($form->isFT3 ? "Y" : "N")."',
                   '".($form->isFT4 ? "Y" : "N")."',
                   '".($form->isHBeAg ? "Y" : "N")."',
                   '".($form->isaHBs ? "Y" : "N")."',
                   '".($form->isM2BPGI ? "Y" : "N")."',
                   '".($form->isAFP ? "Y" : "N")."',
                   '".($form->isFibroScan ? "Y" : "N")."',
                   '".($form->isMDTConsultation ? "Y" : "N")."',
                   '".($form->isPaidHBsAgQ ? "Y" : "N")."',
                   '".($form->isPaidHCV_AbQ ? "Y" : "N")."',
                   '".($form->isPaidTSH ? "Y" : "N")."',
                   '".($form->isPaidFT3 ? "Y" : "N")."',
                   '".($form->isPaidFT4 ? "Y" : "N")."',
                   '".($form->isPaidHBeAg ? "Y" : "N")."',
                   '".($form->isPaidaHBs ? "Y" : "N")."',
                   '".($form->isPaidM2BPGI ? "Y" : "N")."',
                   '".($form->isPaidAFP ? "Y" : "N")."',
                   '".($form->isPaidFibroScan ? "Y" : "N")."',
                   '".($form->isPaidMDTConsultation ? "Y" : "N")."',
                   '".($form->isResHBsAgQ ? "Y" : "N")."',
                   '".($form->isResHCV_AbQ ? "Y" : "N")."',
                   '".($form->isResTSH ? "Y" : "N")."',
                   '".($form->isResFT3 ? "Y" : "N")."',
                   '".($form->isResFT4 ? "Y" : "N")."',
                   '".($form->isResHBeAg ? "Y" : "N")."',
                   '".($form->isResaHBs ? "Y" : "N")."',
                   '".($form->isResM2BPGI ? "Y" : "N")."',
                   '".($form->isResAFP ? "Y" : "N")."',
                   '".($form->isResFibroScan ? "Y" : "N")."',
                   '".($form->isResMDTConsultation ? "Y" : "N")."',

                  '".$form->labbarcode."',
                  '".substr($form->date,0,10)."',
                  '".$userid."',
                  '".date("Y-m-d H:i:s")."',
                  '".$form->note."',
                  '".$form->labid."',
                  '".($form->isPaidAll ? "Y" : "N")."',
                  '".$form->remainPayment."',
                  '".$form->isDiscFibroscan."',

                  '".$form->cartamount."',
                  '".($form->isCash ? "Y" : "N")."',
                  '".($form->isCart ? "Y" : "N")."',
                  '".$form->cashamount."',
                  '".($form->isdiscount ? "Y" : "N")."',
                  '".$form->discountamount."',
                  '".($form->isPaidEMD ? "Y" : "N")."',
                  '".$form->PaidEMD."',
                  '".($form->isresearchdisc ? "Y" : "N")."',
                  '".$form->researchdiscamount."',
                  '".($form->isothercost ? "Y" : "N")."',
                  '".$form->othercostamount."',
                  '".($form->isGenotype ? "Y" : "N")."',
                  '".$form->Genotype."',
                  '".($form->isPaidGenotype ? "Y" : "N")."',
                  '".($form->isResGenotype ? "Y" : "N")."',
                   '".($form->isVitD ? "Y" : "N")."',
                  '".$form->VitD."',
                  '".($form->isPaidVitD ? "Y" : "N")."',
                  '".($form->isResVitD ? "Y" : "N")."',
                   '".($form->isFER ? "Y" : "N")."',
                  '".$form->FER."',
                  '".($form->isPaidFER ? "Y" : "N")."',
                  '".($form->isResFER ? "Y" : "N")."',
                  '".($form->isCoagulo ? "Y" : "N")."',
                  '".$form->Coagulo."',
                  '".($form->isPaidCoagulo ? "Y" : "N")."',
                  '".($form->isResCoagulo ? "Y" : "N")."',
                  '".($form->isBlood ? "Y" : "N")."',
                  '".$form->Blood."',
                  '".($form->isPaidBlood ? "Y" : "N")."',
                  '".($form->isResBlood ? "Y" : "N")."',
                  '".($form->isBiohimi ? "Y" : "N")."',
                  '".$form->Biohimi."',
                  '".($form->isPaidBiohimi ? "Y" : "N")."',
                  '".($form->isResBiohimi ? "Y" : "N")."',
                  '".($form->priceBiohimi)."'

                  )";

$db->startTransaction();

$result = $db->insertQuery($str);
if($result != NULL){

$labrecordid = $result;
$sourceid = $labrecordid;

if($form->isHDV_Ab && $form->isPaidHDV_Ab)
   {
   $result = $db->getOneRecord("select max(positionid) positionid, isactive from mraptray where testtype = 'anti_HDV' group by isactive HAVING (max(positionid) < 45 and isactive = 0) or (isactive = 1) order by isactive");
    $positionid = $result['positionid']? $result['positionid'] + 1 : 1;
      $isactive = $result['isactive'] ? $result['isactive'] : 0;
      $testtype = 'anti_HDV';
   $str_anti_HDV = "insert into mraptray (positionid, isactive, testtype, date, ubchtunid, priority, barcode, reguser, regdate, sourcekey, sourceid)
                values($positionid, $isactive, '$testtype', '$datenow', $ubchtunid, 0, '$barcode', '$reguser', '$regdate', 'mrlabrecord', '$sourceid');";
   }

   if($form->isHBV_DNA && $form->isPaidHBV_DNA)
   {
   $result = $db->getOneRecord("select max(positionid) positionid, isactive from mraptray where testtype = 'HBV' group by isactive HAVING (max(positionid) < 45 and isactive = 0) or (isactive = 1) order by isactive");
    $positionid = $result['positionid']? $result['positionid'] + 1 : 1;
      $isactive = $result['isactive'] ? $result['isactive'] : 0;
      $testtype = 'HBV';
   $str_HBV = "insert into mraptray (positionid, isactive, testtype, date, ubchtunid, priority, barcode, reguser, regdate, sourcekey, sourceid)
                values($positionid, $isactive, '$testtype', '$datenow', $ubchtunid, 0, '$barcode', '$reguser', '$regdate',  'mrlabrecord', '$sourceid');";
   }
   if($form->isHCV_DNA && $form->isPaidHCV_DNA)
   {
   $result = $db->getOneRecord("select max(positionid) positionid, isactive from mraptray where testtype = 'HCV' group by isactive HAVING (max(positionid) < 95 and isactive = 0) or (isactive = 1) order by isactive");
      $positionid = $result['positionid']? $result['positionid'] + 1 : 1;
      $isactive = $result['isactive'] ? $result['isactive'] : 0;
      $testtype = 'HCV';
   $str_HCV = "insert into mraptray (positionid, isactive, testtype, date, ubchtunid, priority, barcode, reguser, regdate, sourcekey, sourceid) values($positionid, $isactive, '$testtype', '$datenow', $ubchtunid, 0,'$barcode', '$reguser', '$regdate', 'mrlabrecord', '$sourceid');";
   }
   if($form->isHDV_DNA && $form->isPaidHDV_DNA)
   {
   $result = $db->getOneRecord("select max(positionid) positionid, isactive from mraptray where testtype = 'HDV' group by isactive HAVING (max(positionid) < 45 and isactive = 0) or (isactive = 1) order by isactive");
      $positionid = $result['positionid'] ? $result['positionid'] + 1 : 1;
      $isactive = $result['isactive'] ? $result['isactive'] : 0;
      $testtype = 'HDV';
      $str_HDV = "insert into mraptray (positionid, isactive, testtype, date, ubchtunid, priority, barcode, reguser, regdate, sourcekey, sourceid)
                values($positionid, $isactive, '$testtype', '$datenow', $ubchtunid, 0, '$barcode', '$reguser', '$regdate', 'mrlabrecord', '$sourceid');";
   }
    if(($form->isHBsAgQ && $form->isPaidHBsAgQ) || ($form->isHBeAg && $form->isPaidHBeAg) || ($form->isaHBs && $form->isPaidaHBs) || ($form->isM2BPGI && $form->isPaidM2BPGI) || ($form->isAFP && $form->isPaidAFP) || ($form->isHCV_AbQ && $form->isPaidHCV_AbQ) || ($form->isTSH && $form->isPaidTSH) || ($form->isFT3 && $form->isPaidFT3) || ($form->isFT4 && $form->isPaidFT4))
   {
   $result = $db->getOneRecord("select max(positionid) positionid, isactive from mraptray where testtype = 'SYSMEX' group by isactive HAVING (max(positionid) < 45 and isactive = 0) or (isactive = 1) order by isactive");
       $positionid = $result['positionid']? $result['positionid'] + 1 : 1;
      $isactive = $result['isactive'] ? $result['isactive'] : 0;
      $testtype = 'SYSMEX';
      $testdetail = ($form->isHBsAgQ && $form->isPaidHBsAgQ ? "HBsAgQ": "") . ($form->isHBeAg && $form->isPaidHBeAg ? "HBeAg" : "") . ($form->isaHBs && $form->isPaidaHBs ? "aHBs" : "") . ($form->isM2BPGI && $form->isPaidM2BPGI ? "M2BPGI" : "") . ($form->isAFP && $form->isPaidAFP ? "AFP" : "") .  ($form->isHCV_AbQ && $form->isPaidHCV_AbQ ? "HCV_AbQ" : "") . ($form->isTSH && $form->isPaidTSH ? "TSH" : "") . ($form->isFT3 && $form->isPaidFT3 ? "FT3" : "") . ($form->isFT4 && $form->isPaidFT4 ? "FT4" : "");
      $str_SYSMEX = "insert into mraptray (positionid, isactive, testtype, date, ubchtunid, priority, barcode, testdetail, reguser, regdate, sourcekey, sourceid)
                values($positionid, $isactive, '$testtype', '$datenow', $ubchtunid, 0, '$barcode', '$testdetail','$reguser', '$regdate', 'mrlabrecord', '$sourceid');";
   }
    if($form->isGenotype && $form->isPaidGenotype)
   {
      $result = $db->getOneRecord("select max(positionid) positionid, isactive from mraptray where testtype = 'GENOTYPE' group by isactive HAVING (max(positionid) < 45 and isactive = 0) or (isactive = 1) order by isactive");
      $positionid = $result['positionid']? $result['positionid'] + 1 : 1;
      $isactive = $result['isactive'] ? $result['isactive'] : 0;
      $testtype = 'GENOTYPE';
    $result = $db->getOneRecord("SELECT count(1) cnt FROM mraptrayhist WHERE testtype = 'GENOTYPE' and barcode = '$barcode'");
    if(($result != null and $result["cnt"] > 0)) $str_Genotype = "";
    else $str_Genotype = "insert into mraptray (positionid, isactive, testtype, date, ubchtunid, priority, barcode, reguser, regdate, sourcekey, sourceid)
                values($positionid, $isactive, '$testtype', '$datenow', $ubchtunid, 0, '$barcode', '$reguser', '$regdate', 'mrlabrecord', '$sourceid');";
   }

    if($form->isVitD && $form->isPaidVitD)
   {
      $result = $db->getOneRecord("select max(positionid) positionid, isactive from mraptray where testtype = 'VITD' group by isactive HAVING (max(positionid) < 45 and isactive = 0) or (isactive = 1) order by isactive");
      $positionid = $result['positionid']? $result['positionid'] + 1 : 1;
      $isactive = $result['isactive'] ? $result['isactive'] : 0;
      $testtype = 'VITD';
    $result = $db->getOneRecord("SELECT count(1) cnt FROM mraptrayhist WHERE testtype = 'VITD' and barcode = '$barcode'");
    if(($result != null and $result["cnt"] > 0)) $str_VitD = "";
    else $str_VitD = "insert into mraptray (positionid, isactive, testtype, date, ubchtunid, priority, barcode, reguser, regdate, sourcekey, sourceid)
                values($positionid, $isactive, '$testtype', '$datenow', $ubchtunid, 0, '$barcode', '$reguser', '$regdate', 'mrlabrecord', '$sourceid');";
   }

    if($form->isFER && $form->isPaidFER)
   {
      $result = $db->getOneRecord("select max(positionid) positionid, isactive from mraptray where testtype = 'FER' group by isactive HAVING (max(positionid) < 45 and isactive = 0) or (isactive = 1) order by isactive");
      $positionid = $result['positionid']? $result['positionid'] + 1 : 1;
      $isactive = $result['isactive'] ? $result['isactive'] : 0;
      $testtype = 'FER';
    $result = $db->getOneRecord("SELECT count(1) cnt FROM mraptrayhist WHERE testtype = 'FER' and barcode = '$barcode'");
    if(($result != null and $result["cnt"] > 0)) $str_FER = "";
    else $str_FER = "insert into mraptray (positionid, isactive, testtype, date, ubchtunid, priority, barcode, reguser, regdate, sourcekey, sourceid)
                values($positionid, $isactive, '$testtype', '$datenow', $ubchtunid, 0, '$barcode', '$reguser', '$regdate', 'mrlabrecord', '$sourceid');";
   }

  if($str_HBV != "") { $result = $db->insertQuery($str_HBV); if($result == NULL) { $error = true;}}
  if($str_HCV != "")  { $result = $db->insertQuery($str_HCV); if($result == NULL) { $error = true;}}
  if($str_HDV != "") { $result = $db->insertQuery($str_HDV); if($result == NULL) { $error = true;}}
  if($str_anti_HDV != "") { $result = $db->insertQuery($str_anti_HDV); if($result == NULL) { $error = true;}}
  if($str_SYSMEX != "") { $result = $db->insertQuery($str_SYSMEX); if($result == NULL) { $error = true;}}
  if($str_Genotype != "") { $result = $db->insertQuery($str_Genotype); if($result == NULL) { $error = true;}}
  if($str_VitD != "") { $result = $db->insertQuery($str_VitD); if($result == NULL) { $error = true;}}
  if($str_FER != "") { $result = $db->insertQuery($str_FER); if($result == NULL) { $error = true;}}

//-----update patient data--------
         if($form->isPaidHBsAg || $form->isPaidHCV_Ab || $form->isPaidHDV_Ab){

         $ins = "insert into sh_hepatittest (ubchtunid, date, HBsAg, anti_HCV, anti_HDV, sourcekey, sourceid, reguser, regdate)
                    values(".$ubchtunid.",'".substr($form->date,0,10)."', '".($form->isPaidHBsAg && $form->HBsAg ? "Y" : ($form->isPaidHBsAg && !$form->HBsAg ? "N" : ""))."','".($form->isPaidHCV_Ab && $form->HCV_Ab ? "Y" : ($form->isPaidHCV_Ab && !$form->HCV_Ab ? "N" : ""))."', '".($form->isPaidHDV_Ab && $form->HDV_Ab ? "Y" : ($form->isPaidHDV_Ab && $form->HDV_Ab ? "N" : ""))."','mrlabrecord', ".$labrecordid.", 0 ,'".date("Y-m-d H:i:s")."')";

         $result = $db->insertQuery($ins); if($result == NULL) { $error = true;}
            }
            if($form->isPaidHIV || $form->isPaidSyphilis){
         $ins = "insert into sh_other (ubchtunid, date, title, code, result, unit, sourcekey, sourceid, reguser, regdate)
                    values(".$ubchtunid.",'".substr($form->date,0,10)."', 'HIV', 'HIV', '".($form->isPaidHIV && $form->HIV ? "Y" : ($form->isPaidHIV && !$form->HIV ? "N" : ""))."','','mrlabrecord', ".$labrecordid.", 0 ,'".date("Y-m-d H:i:s")."')";
         $result = $db->insertQuery($ins); if($result == NULL) { $error = true;}
            }
             if($form->isPaidSyphilis){
         $ins = "insert into sh_other (ubchtunid, date, title, code, result, unit, sourcekey, sourceid, reguser, regdate)
                    values(".$ubchtunid.",'".substr($form->date,0,10)."', 'Syphilis', 'Syphilis', '".($form->isPaidSyphilis && $form->Syphilis ? "Y" : ($form->isPaidSyphilis && !$form->Syphilis ? "N" : ""))."','','mrlabrecord', ".$labrecordid.", 0 ,'".date("Y-m-d H:i:s")."')";
         $result = $db->insertQuery($ins); if($result == NULL) { $error = true;}
            }
        if($form->isPaidHCV_DNA || $form->isPaidHBV_DNA || $form->isPaidHDV_DNA){
         $ins = "insert into sh_nuklein (ubchtunid, date, HCV_RNA, HBV_DNA, HDV_RNA, sourcekey, sourceid, reguser, regdate)
                    values (".$ubchtunid.",'".substr($form->date,0,10)."','".($form->isPaidHCV_DNA ? $form->HCV_DNA : "")."',
                        '".($form->isPaidHBV_DNA ? $form->HBV_DNA_ul: "")."',
                        '".($form->isPaidHDV_DNA ? $form->HDV_DNA : "")."', 'mrlabrecord', ".$labrecordid.", 0,'".date("Y-m-d H:i:s")."')";

         $result = $db->updateQuery($ins); if($result == NULL) { $error = true;}
            }

        if($form->isPaidHBsAgQ || $form->isPaidHBeAg || $form->isPaidAFP || $form->isPaidVitD || $form->isPaidM2BPGI || $form->isPaidaHBs || $form->isPaidGenotype || $form->isPaidHCV_AbQ  || $form->isPaidTSH || $form->isPaidFT3  || $form->isPaidFT4 ){
         $ins = "insert into sh_sismeks (address, ubchtunid, date, Genotype, HBsAgQ, anti_HCV, TSH, FreeT3, FreeT4, HBeAg, AFP, VitD,  M2BPGi, anti_HBs, sourcekey, sourceid, reguser, regdate) values (1, ".$ubchtunid.",'".substr($form->date,0,10)."','".($form->isPaidGenotype ? $form->Genotype : "")."','".($form->isPaidHBsAgQ ? $form->HBsAgQ : "")."','".($form->isPaidHCV_AbQ ? $form->HCV_AbQ : "")."','".($form->isPaidTSH ? $form->TSH : "")."', '".($form->isPaidFT3 ? $form->FT3 : "")."', '".($form->isPaidFT4 ? $form->FT4 : "")."' , '".($form->isPaidHBeAg ? $form->HBeAg: " ")."', '".($form->isPaidAFP ? $form->AFP: " ")."', '".($form->isPaidVitD ? $form->VitD: " ")."', '".($form->isPaidM2BPGI ? $form->M2BPGI: " ")."', '".($form->isPaidaHBs ? $form->aHBs : " ")."', 'mrlabrecord', ".$labrecordid.", 0,'".date("Y-m-d H:i:s")."')";

         $result = $db->updateQuery($ins); if($result == NULL) { $error = true;}
            }
            if($form->isPaidFER) $result = $db->insertQuery("insert into sh_biohimi (address, ubchtunid, date, FER, sourcekey, sourceid, reguser, regdate) values(1, ".$ubchtunid.",'".substr($form->date,0,10)."','".($form->isPaidFER ? $form->FER : "")."','mrlabrecord', ".$labrecordid.", 1,'".date("Y-m-d H:i:s")."')"); if($result == NULL) { $error = true;}

        if($form->isPaidBiohimi && $form->isBiohimi)
        {
          $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'GGT', 'GGT', '".($form->BiohimiD->isGGT ? 'Y' : 'N')."','".($form->BiohimiD->isPaidGGT ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
          $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'TBIL', 'TBIL', '".($form->BiohimiD->isTBIL ? 'Y' : 'N')."','".($form->BiohimiD->isPaidTBIL ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'DBIL', 'DBIL', '".($form->BiohimiD->isDBIL ? 'Y' : 'N')."','".($form->BiohimiD->isPaidDBIL ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'ALP', 'ALP', '".($form->BiohimiD->isALP ? 'Y' : 'N')."','".($form->BiohimiD->isPaidALP ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'ALT', 'ALT', '".($form->BiohimiD->isALT ? 'Y' : 'N')."','".($form->BiohimiD->isPaidALT ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'AST', 'AST', '".($form->BiohimiD->isAST ? 'Y' : 'N')."','".($form->BiohimiD->isPaidAST ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'ALB', 'ALB', '".($form->BiohimiD->isALB ? 'Y' : 'N')."','".($form->BiohimiD->isPaidALB ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'Total Protein', 'TP', '".($form->BiohimiD->isTP ? 'Y' : 'N')."','".($form->BiohimiD->isPaidTP ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'LDH', 'LDH', '".($form->BiohimiD->isLDH ? 'Y' : 'N')."','".($form->BiohimiD->isPaidLDH ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'CREA', 'CREA', '".($form->BiohimiD->isCREA ? 'Y' : 'N')."','".($form->BiohimiD->isPaidCREA ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'UA', 'UA', '".($form->BiohimiD->isUA ? 'Y' : 'N')."','".($form->BiohimiD->isPaidUA ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'BUN', 'BUN', '".($form->BiohimiD->isBUN ? 'Y' : 'N')."','".($form->BiohimiD->isPaidBUN ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'Ca', 'Ca', '".($form->BiohimiD->isCa ? 'Y' : 'N')."','".($form->BiohimiD->isPaidCa ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'Fe', 'Fe', '".($form->BiohimiD->isFe ? 'Y' : 'N')."','".($form->BiohimiD->isPaidFe ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'P', 'P', '".($form->BiohimiD->isP ? 'Y' : 'N')."','".($form->BiohimiD->isPaidP ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
           $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'Mg', 'Mg', '".($form->BiohimiD->isMg ? 'Y' : 'N')."','".($form->BiohimiD->isPaidMg ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'Cl', 'Cl', '".($form->BiohimiD->isCl ? 'Y' : 'N')."','".($form->BiohimiD->isPaidCl ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'Lipase', 'Lipase', '".($form->BiohimiD->isLipase ? 'Y' : 'N')."','".($form->BiohimiD->isPaidLipase ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'GLUCOSE', 'GLU', '".($form->BiohimiD->isGLU ? 'Y' : 'N')."','".($form->BiohimiD->isPaidGLU ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'HemoglobinA1c', 'HemoglobinA1c', '".($form->BiohimiD->isHemoglobinA1c ? 'Y' : 'N')."','".($form->BiohimiD->isPaidHemoglobinA1c ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'AMYLASE', 'AMY', '".($form->BiohimiD->isAMY ? 'Y' : 'N')."','".($form->BiohimiD->isPaidAMY ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'LDL', 'LDL', '".($form->BiohimiD->isLDL ? 'Y' : 'N')."','".($form->BiohimiD->isPaidLDL ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'HDL', 'HDL', '".($form->BiohimiD->isHDL ? 'Y' : 'N')."','".($form->BiohimiD->isPaidHDL ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'TC', 'TC', '".($form->BiohimiD->isTC ? 'Y' : 'N')."','".($form->BiohimiD->isPaidTC ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'Tryglycerides', 'Tryglycerides', '".($form->BiohimiD->isTryglycerides ? 'Y' : 'N')."','".($form->BiohimiD->isPaidTryglycerides ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'ASO', 'ASO', '".($form->BiohimiD->isASO ? 'Y' : 'N')."','".($form->BiohimiD->isPaidASO ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'CRP', 'CRP', '".($form->BiohimiD->isCRP ? 'Y' : 'N')."','".($form->BiohimiD->isPaidCRP ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            $result = $db->insertQuery("insert into mrlabrecorddetail (ubchtunid, labrecordid, labid, title, code, isChecked, isPaid, regdate, reguser) values($ubchtunid, $labrecordid, 1, 'RF', 'RF', '".($form->BiohimiD->isRF ? 'Y' : 'N')."','".($form->BiohimiD->isPaidRF ? 'Y' : 'N')."','$regdate',$reguser)"); if($result == NULL) { $error = true;}
            }
//-----update patient data--------



      if($error == false) $db->commitTransaction();
         else $db->rollbackTransaction();
  } else $db->rollbackTransaction();
break;
}
if($error == false)
{
            $response["status"] = "success";
            $response["message"] = "Амжилттай хадгаллаа.";
            $response["labrecordid"] = $labrecordid;
            echoResponse(200, $response);
}
else{
        $response["status"] = "error";
        $response["message"] = "Хадгалахад алдаа гарлаа!";
        echoResponse(201, $response);
    }
});

//
//inComeSave
//

$app->post('/inComeSave', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $form = $r->params->form;
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];


if($form->mode != "edit"){
    $str = "insert into `income` (  `drugsupplierid`,
                  `distributerid`,
                  `date`,
                  `drugid`,
                  `totalqty`,
                  `reguser` ,
                  `regdate`,
                  `note`
                )
                values(".$form->drugsupplierid.",
                  ".$form->distributerid.",
                  '".substr($form->date,0,10)."',
                  ".$form->drugid.",
                  ".$form->unitqty.",
                  '".$userid."',
                  '".date("Y-m-d H:i:s")."',
                  '".$form->note."'
                  )";
$result = $db->insertQuery($str);
} else {
$str = "update `income` set `note` = '".$form->note."'
                  where `id` = ".$form->comeid."
               ";
$comeid = $form->comeid;
$result = $db->updateQuery($str);
}
if($result != NULL)
{
 if($form->mode != "edit")
 {
  $comeid = $result;
  $stru1 = "update `instock` set `unitqty` = `unitqty` + ".$form->unitqty." where `code` = '01' and `distributerid` = ".$form->distributerid;
  $db->updateQuery($stru1);

for ( $i=0; $i < $form->unitqty; $i++) {

        $strR="insert into `incomedetail` (  `comeid`,
               `drugserialnumber`,
               `drugid`,
               `unitqty`,
               `reguser` ,
               `regdate`
            )
            values(".$comeid.",
              '".$form->grid[$i]->drugserialnumber."',
              ".$form->drugid.",
              1,
               '".$userid."',
              '".date("Y-m-d H:i:s")."'
              )
            ";
        $db->insertQuery($strR);
                                                     }
}


            $response["status"] = "success";
            $response["message"] = "Амжилттай хадгаллаа.";
            $response["comeid"] = $comeid;
            echoResponse(200, $response);

}
else{
        $response["status"] = "error";
        $response["message"] = "Хадгалахад алдаа гарлаа!";
        echoResponse(201, $response);
    }
});

//
//inTransferSave
//

$app->post('/inTransferSave', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $form = $r->params->form;
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];

$result = $db->getOneRecord("select `unitqty` from instock  where `id` ='".$form->fromstockid."'");
 if($result)
 {
  if($result["unitqty"] > $form->unitqty)
    {

if($form->mode != "edit"){
    $str = "insert into `instockdiary` (  `fromstockid`,
                  `tostockid`,
                  `date`,
                  `status`,
                  `drugid`,
                  `initialqty`,
                  `unitqty`,
                  `reguser` ,
                  `regdate`,
                  `note`
                )
                values(".$form->fromstockid.",
                  ".$form->tostockid.",
                  '".substr($form->date,0,10)."',
                  '".$form->status."',
                  ".$form->drugid.",
                  (select unitqty from `instock` where id = ".$form->tostockid."),
                  ".$form->unitqty.",
                  '".$userid."',
                  '".date("Y-m-d H:i:s")."',
                  '".$form->note."'
                  )";
$result = $db->insertQuery($str);
} else {
$str = "update `instockdiary` set `status` =  '".$form->status."',
                  `note` = '".$form->note."'
                  where `id` = ".$form->stockdiaryid."
               ";
$stockdiaryid = $form->stockdiaryid;
$result = $db->updateQuery($str);
}
if($result != NULL)
{
 if($form->mode != "edit")
 {
  $stockdiaryid = $result;
  $stru1 = "update `instock` set `unitqty` = `unitqty` - ".$form->unitqty." where `id` = ".$form->fromstockid;
  $stru2 = "update `instock` set `unitqty` = `unitqty` + ".$form->unitqty." where `id` = ".$form->tostockid;
  $db->updateQuery($stru1);
  $db->updateQuery($stru2);

for ( $i=0; $i < $form->unitqty; $i++) {

        $strR="insert into `instockdiarydetail` (  `stockdiaryid`,
               `drugserialnumber`,
               `drugid`,
               `unitqty`,
               `reguser` ,
               `regdate`
            )
            values(".$stockdiaryid.",
              '".$form->grid[$i]->drugserialnumber."',
              ".$form->drugid.",
              1,
               '".$userid."',
              '".date("Y-m-d H:i:s")."'
              )
            ";
        $db->insertQuery($strR);

        $stru3 = "update incomedetail set stockid = ".$form->tostockid." where drugserialnumber = ".$form->grid[$i]->drugserialnumber;
        $db->updateQuery($stru3);

                                                     }
}


            $response["status"] = "success";
            $response["message"] = "Амжилттай хадгаллаа.";
            $response["stockdiaryid"] = $stockdiaryid;
            echoResponse(200, $response);

}
else{
        $response["status"] = "error";
        $response["message"] = "Хадгалахад алдаа гарлаа!";
        echoResponse(201, $response);
    }
    } else {  $response["status"] = "error";
        $response["message"] = "Агуулахад шилжүүлэх тоо хүрэхгүй байна! Боломжит тоо:".$result["unitqty"];
        echoResponse(201, $response);
      }
    }
});
//
//  inDispenseSave
//
$app->post('/inDispenseSave', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $form = $r->params->form;
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];
    $drugserialnumber = $form->drugserialnumber;
    $dispenseid = $form->dispenseid;
    $ubchtunid = $form->ubchtunid;
$result = $db->getOneRecord("select s.id from instock s inner join doctors_main d on d.branchid = s.branchid where userid='$userid'");
 if($result)
 {
  $stockid = $result["id"];

$result = $db->getOneRecord("select sdd.drugid from instockdiarydetail sdd left join instockdiary sd on sdd.stockdiaryid = sd.id  where sdd.ubchtunid is null and sdd.drugserialnumber= '$drugserialnumber' and sd.tostockid = '$stockid'");
 if($result)
 {
   $drugid = $result["drugid"];

   $result = $db->getOneRecord("select count(*) cnt, ed.id emchilgeedetid from ff_emchilgee e left join ff_emchilgee_det ed on ed.emchilgeeid = e.id where e.ubchtunid = '$ubchtunid' and ed.unit > ed.giveunit and ed.emid = '$drugid'");
 if($result &&  $result["cnt"] > 0)
 {
  $emchilgeedetid = $result["emchilgeedetid"];

          if($form->mode != "edit"){
              $str = "insert into `indispense` (  `ubchtunid`,
                            `reguser` ,
                            `regdate`,
                            `note`
                          )
                          values(".$ubchtunid.",
                            '".$userid."',
                            '".date("Y-m-d H:i:s")."',
                            '".$form->note."'
                            )";
                $result = $db->insertQuery($str);
              }
              else {
              $str = "update `indispense` set `note` = '".$form->note."'
                                where `id` = ".$dispenseid."
                             ";
              $result = $db->updateQuery($str);
              }
            if($result != NULL)
            {
                         if($form->mode != "edit")
                         {
                          $dispenseid = $result;
                         }


                          $stru1 = "update `instock` set `unitqty` = `unitqty` - 1 where `id` = ".$stockid;
                          $db->updateQuery($stru1);

                          $stru2 = "update `instockdiarydetail` set `ubchtunid` = ".$ubchtunid." where `drugserialnumber` = '$drugserialnumber'";
                          $db->updateQuery($stru2);

                          $stru1 = "update `ff_emchilgee_det` set `giveunit` = `giveunit` + 1 where `id` = ".$emchilgeedetid;
                          $db->updateQuery($stru1);

                                $strR="insert into `indispensedetail` (  `dispenseid`,
                                       `drugserialnumber`,
                                       `drugid`,
                                       `stockid`,
                                       `unitqty`,
                                       `reguser`,
                                       `date`,
                                       `regdate`
                                    )
                                    values(".$dispenseid.",
                                      '".$drugserialnumber."',
                                      ".$drugid.",
                                      ".$stockid.",
                                      1,
                                       '".$userid."',
                                      '".date("Y-m-d H:i:s")."',
                                      '".date("Y-m-d H:i:s")."'
                                      )
                                    ";
                        $db->insertQuery($strR);
                        $response["status"] = "success";
                        $response["message"] = "Амжилттай хадгаллаа.";
                        $response["id"] = $dispenseid;
                        echoResponse(200, $response);

              }
                  else{
                          $response["status"] = "error";
                          $response["message"] = "Хадгалахад алдаа гарлаа!";
                          echoResponse(201, $response);
                      }
                    }
                    else {
                $response["status"] = "error";
                $response["message"] = "Эмчилгээ бичигдээгүй эсвэл тухайн эмийг авсан байна.";
                echoResponse(201, $response);

                    }
  }
         else {
                $response["status"] = "error";
                $response["message"] = "Тухайн сериал дугаартай эм тухайн салбарт бүртгэлгүй байна.";
                echoResponse(201, $response);
              }
    }
     else {
        $response["status"] = "error";
        $response["message"] = "Хадгалахад алдаа гарлаа!";
        echoResponse(201, $response);
      }

});


//
//changePass
//

$app->post('/passChangeDoctor', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $form = $r->params->form;
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];
    $result_exist = true;
if($form->userid != "")
 { $str = "update `doctors_main` set `password` =  md5('".$form->password."')
                  where `userid` = '".$form->userid."'"; }
else { $result_exist = $db->getOneRecord("select * from doctors_main where userid = '".$userid."' and password =  md5('".$form->password."')");
       $str = "update `doctors_main` set `password` =  md5('".$form->newpassword."')
                  where `userid` = '".$userid."' and `password` =  md5('".$form->password."')"; }
$result = $db->updateQuery($str);
if($result != NULL && $result_exist)
    {
                $response["status"] = "success";
                $response["message"] = "Амжилттай хадгаллаа.";
                echoResponse(200, $response);
    }
else{
        $response["status"] = "error";
        $response["message"] = "Хадгалахад алдаа гарлаа!";
        echoResponse(201, $response);
    }
});

$app->post('/passChangePatient', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $form = $r->params->form;
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];

$str = "update `ubchtun_main` set `password` =  md5('".$form->password."')
                  where UPPER(`rd`) = UPPER('".$form->rd."')";
$result = $db->updateQuery($str);
if($result != NULL)
    {
                $response["status"] = "success";
                $response["message"] = "Амжилттай хадгаллаа.";
                echoResponse(200, $response);
    }
else{
        $response["status"] = "error";
        $response["message"] = "Хадгалахад алдаа гарлаа!";
        echoResponse(201, $response);
    }
});


//
//mrLabm2000Save
//

$app->post('/mrLabm2000Save', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $form = $r->params->form;
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];

    $error = false;
    $strR = "";
    $strTrayHist = "";
    $instr = "";
    $upstr = "";
    $errormsg = "";
    $ustr = "";
    $db->startTransaction();
$labm2000id = 0;
if($form->mode != "edit"){
    $instr = "insert into `mrlabm2000` (
                      `labid`,
                      `date`,
                      `testtype`,
                      `runtime`,
                      `platename`,
                      `dwplatename`,
                      `serlot`,
                      `serexpiration`,
                      `sectime`,
                      `mmactime`,
                      `controllot`,
                      `controllevels`,
                      `calibratorlot`,
                      `actime`,
                      `calibratorlevels`,
                      `doctorid`,
                      `pcrrexpiration`,
                      `pcrrlot`,
                      `assaylot`,
                      `qclot`,
                      `reguser`,
                      `regdate`
                )
                values(".$form->labid.",
                  '".substr($form->date,0,10)."',
                  '".$form->testtype."',
                  '".$form->runtime."',
                  '".$form->platename."',
                  '".$form->dwplatename."',
                  '".$form->serlot."',
                  '".$form->serexpiration."',
                  '".$form->sectime."',
                  '".$form->mmactime."',
                  '".$form->controllot."',
                  '".$form->controllevels."',
                  '".$form->calibratorlot."',
                  '".$form->actime."',
                  '".$form->calibratorlevels."',
                  '".$form->doctorid."',

                  '".$form->pcrrexpiration."',
                  '".$form->pcrrlot."',
                  '".$form->assaylot."',
                  '".$form->qclot."',

                  '".$userid."',
                  '".date("Y-m-d H:i:s")."'
                  )";
$result = $db->insertQuery($instr);
if($result == NULL) { $error = true; }
else $labm2000id = $result;
} else {
$upstr = "update `mrlabm2000` set `testtype` = '".$form->testtype."',
                 `date` = '".substr($form->date,0,10)."',
                 `runtime` = '".$form->runtime."',
                 `platename` = '".$form->platename."',
                 `dwplatename` = '".$form->dwplatename."',
                 `serlot` = '".$form->serlot."',
                 `serexpiration` = '".$form->serexpiration."',
                 `sectime` = '".$form->sectime."',
                 `mmactime` = '".$form->mmactime."',
                 `controllot` = '".$form->controllot."',
                 `controllevels` = '".$form->controllevels."',
                 `calibratorlot` = '".$form->calibratorlot."',
                 `pcrrexpiration` = '".$form->pcrrexpiration."',
                 `pcrrlot` = '".$form->pcrrlot."',
                 `assaylot` = '".$form->assaylot."',
                 `qclot` = '".$form->qclot."',
                 `actime` = '".$form->actime."',
                 `calibratorlevels` = '".$form->calibratorlevels."',
                 `doctorid` = '".$form->doctorid."'
                  where `id` = ".$form->labm2000id."
               ";
$labm2000id = $form->labm2000id;
$result = $db->updateQuery($upstr);
if($result == NULL) $error = true;
}
if(!$error)
{
               $rows =  array();
               $result = $db->getRecord("SELECT * from mrlabm2000detail where  labm2000id = $labm2000id");
               if($result == NULL) $error = true;
               else {

                            while($r =$result->fetch_object())
                               {
                                 $rows[] = $r;
                               }

                        $grid = array();
                        for ($i=0; $i < $form->unitqty; $i++)
                                    {
                                        $isexit = false;
                                    foreach($rows as $value)
                                          {
                                            if($value->barcode == $form->grid[$i]->barcode){
                                              $result =  $db->updateQuery("UPDATE `mrlabm2000detail` SET `check` = '".$form->grid[$i]->check."' where `barcode` = '".$form->grid[$i]->barcode."' and `testtype` = '".$form->grid[$i]->testtype."'"); if($result == NULL) $error = true;
                                              $isexit = true;
                                              break;
                                            }
                                          }
                                          if(!$isexit)
                                          $grid[] = $form->grid[$i];
                                     }

            for ( $i=0; $i < count($grid); $i++)
                {
                    $strR="insert into `mrlabm2000detail` (`labm2000id`,
                           `ubchtunid`,
                           `barcode`,
                           `check`,
                           `labrecordid`,
                           `testtype`,
                           `date`,
                           `reguser` ,
                           `regdate`
                        )
                        values(".$labm2000id.",
                          '".$grid[$i]->ubchtunid."',
                          '".$grid[$i]->barcode."',
                          '".(isset($grid[$i]->check) ? $grid[$i]->check : 'N')."',
                          '".$grid[$i]->sourceid."',
                          '".$form->testtype."',
                          '".$form->date."',
                          '".$userid."',
                          '".date("Y-m-d H:i:s")."'
                          )
                        ";

                   $result =  $db->insertQuery($strR); if($result == NULL) { $error = true;  $errormsg = $errormsg . "$" . $strR; }
                   $regdate = date("Y-m-d H:i:s");

                   $strTrayHist = "INSERT INTO mraptrayhist (runid, rundate, testdetail, positionid, testtype, date, ubchtunid, priority, barcode, reguser, regdate, sourcekey, sourceid)
                   select $labm2000id, '$regdate', testdetail, positionid, testtype, date, ubchtunid, priority, barcode, '$userid', '$regdate', sourcekey, sourceid from mraptray where testtype = '".$form->testtype."' and sourceid = '".$grid[$i]->sourceid."'";
                   $result =  $db->insertQuery($strTrayHist);
                   if($result == NULL) { $error = true;  $errormsg = $errormsg . "$" . $strTrayHist; }

                   $result =  $db->deleteRecord("DELETE FROM mraptray where testtype = '".$form->testtype."' and sourceid = '".$grid[$i]->sourceid."'");
                   if($result == NULL) $error = true;

                   $ustr = "update mrlabstorage set isresult = 'Y' where SUBSTRING(barcode,1,16) = SUBSTRING('".$grid[$i]->barcode."',1,16)";
                   $result =  $db->updateQuery($ustr); if($result == NULL) { $error = true;  $errormsg = $errormsg . "$" . $ustr; }
                }
            }

}

if(!$error)
{
  $response["status"] = "success";
            $response["message"] = "Амжилттай хадгаллаа.";
            $response["labm2000id"] = $labm2000id;
            echoResponse(200, $response);
            $db->commitTransaction();

}
else {
        $response["status"] = "error";
        $response["message"] = "Хадгалахад алдаа гарлаа! ".$errormsg;
        echoResponse(201, $response);
        $db->rollbackTransaction();
    }
});


//
//saveFormInfo
//

$app->post('/saveFormInfo', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $modal = $r->modal;
    $response = array();
    $db = new DbHandler();
    $session = $db->getSession();
    $userid= $session['userid'];
    $tablename = $modal->tablename;
     $strr = "";

     if($modal->mode == "edit"){
        $wstr = "where id = ";
        $str = "update ".$tablename." set";
            foreach($modal->data as $key=>$value) {
              if (!preg_match('/\d\z/', $key)){
                switch($key) {
                  case 'reguser':
                  case 'regdate':
                  case 'password':
                  break;
                  default:
                  $str = substr($str,-3) == "set" ? $str ." ".$key." = '".$value."'" : $str . " , ".$key." = '".$value."'";
                  if($key == "id") $wstr = $wstr . $value;
                  break;
              }
                 }
            }
            $str = $str . $wstr;
    $result = $db->updateQuery($str);
    if($result != NULL)
            {
            $response["status"] = "success";
            $response["message"] = "Амжилттай хадгаллаа.";
              $response["data"] = $modal->data;
            $response["metadata"] = $modal->metadata;
            $response["string"] = $strr;
             $response["str"] = $str;
            echoResponse(200, $response);

            }
            else {
                $response["status"] = "error";
                $response["message"] = "Хадгалахад алдаа гарлаа!";
                echoResponse(201, $response);
            }
} else {
  $str = "insert into ".$tablename." (";
            foreach($modal->data as $key=>$value) {
                if (!preg_match('/\d\z/', $key)){
                      switch($key){
                        case 'id':
                         break;
                        default:
                          $str = substr($str,-1) == "(" ? $str . $key : $str . " , ". $key;
                        break;
                        }
                 }
            }
            $str = $str . ") values (";
            foreach($modal->data as $key=>$value) {
                   if (!preg_match('/\d\z/', $key)){
                    switch($key){
                        case 'id':
                        break;
                        case 'reguser':
                          $str = substr($str,-1) == "(" ? $str ."'". $userid ."'" : $str . " , '". $userid ."'";
                        break;
                        case 'regdate':
                          $str = substr($str,-1) == "(" ? $str ."'". date('Y-m-d H:i:s') ."'" : $str . " , '". date('Y-m-d H:i:s') ."'";
                        break;
                        case 'password':
                          $str = substr($str,-1) == "(" ? $str ."'". md5($value) ."'" : $str . " , '". md5($value)  ."'";
                        break;
                        default:
                          $str = substr($str,-1) == "(" ? $str ."'". $value ."'" : $str . " , '". $value."'";
                        break;
                    }
                    }
            }
            $str = $str . ")";
 $result = $db->insertQuery($str);
 if($result != NULL)
            {
            $response["status"] = "success";
            $response["message"] = "Амжилттай хадгаллаа.";
            echoResponse(200, $response);

            }
            else {
                $response["status"] = "error";
                $response["message"] = "Хадгалахад алдаа гарлаа!";
                echoResponse(201, $response);
            }

}



});

?>
