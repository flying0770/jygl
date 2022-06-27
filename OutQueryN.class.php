<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-05-18
 * Time: 21:27
 */

require_once 'Common.class.php';
require_once 'funs.php';
// 出货查询

class OutQueryN extends Common
{
//    public static $colums=[
//        "客户编号","客户名称","电话","地址","城市","省份","邮政编码","地区","国家","开户银行","银行账号","纳税号","传真","电子邮件","网址","备注","客户类型","行业类别","客户来源","客户状态","完工","到期","结完","目前使用合同编号","结转","结算工程名称","结转日期","分公司列表"
//    ];
    public static $colums='*';
    static $tablname='V_通用_发货单';
    static $fldSort='';
    static $ID='';

    // Read 获取某ID的记录内容
    public static function Read( $class , $id)
    {
        echo '{"code":1,"res":"fail","id":"","msg":"Query modal not support Read"}';
    }
    // New option 获取添加客户时，应该传过去的选项
    public static function NewOpts( $class,$ver){
        $curVer = 1.0033;    // 当前版本
        if ($curVer>$ver) {
            echo '{"ver":"'.$curVer.'","reType":"自动刷新","cols":[
                                      { "type":"day","format":"YYYY-MM-DD","def":" 00:00:00","unit":"days","st":[0,0,0,8,0,0],"ed":[0,0,1,8,0,0],"index":0},
                                      { "type":"month","format":"YYYY-MM","def":"-01 00:00:00","unit":"months","st":[0,-1,25,8,0,0],"ed":[0,0,25,8,0,0],"index":0 },
                                      { "type":"year","format":"YYYY","def":"-01-01 00:00:00","unit":"years","st":[0,-1,25,8,0,0],"ed":[0,11,25,8,0,0] }
                                      ]}';
            Funs::flog($class,"获取选项",$curVer);
        } else  {
            Funs::flog($class,"获取选项",$curVer);
            echo '{"ver":"'.$curVer.'"}';
        }
    }
    // Create 添加客户，对返回来的数据进行处理,并将结果传回到客户
    public static function Create( $class , $id , $data) {
        echo '{"code":1,"res":"fail","id":"","msg":"Query modal not support Delete"}';
    }
    // List
    public static function Lists( $class , $para )
    {
        if (parent::$debug_mode) echo "动态加载 $class.列表"."</br>";
        $database = self::getDB();

        $start = isset($para["start"])?$para["start"]:1;
        $end= isset($para["end"])?$para["end"]:100;
        $where = isset($para["where"])? json_decode($para["where"],TRUE):[];;

        if ( $where ){
            $conditions =str_replace("WHERE","",$database->where_clause($where));
        } else {
            echo  json_encode(Funs::auto_charset(["count" =>-1,"pageCount"=>1,"results"=>[]]));
            return ;
        }
        if (!isset($para["grp"])) {
            $sql = "SELECT pName,pPart,G,P,SUM(V) AS V,
                      SUM(c) AS c, SUM(xV) xV, SUM(backV) backV, 
                      SUM(rBackV) rBackV, SUM(realV) realV, 
                      SUM(drag_p) AS drag_p, SUM(signV) signV,
                      SUM(setV) AS setV
                FROM [PV_Out] a
                WHERE $conditions 
                GROUP BY pName,pPart,G,P
                ORDER BY pName DESC, pPart, G asc
                ";
        } else {
            $sql = "SELECT aa.*,
                case when isnull(tID,'')='' then substring(dd.工程名称,1,50) else substring(cc.具体工程名称,1,50) end pName,
                cc.施工部位 pPart,cc.砼强度等级 G,cc.浇注方式 P,cc.进行情况 R
                FROM 
                (
                    SELECT b.合同编号 cID,
                        a.任务编号 tID,
                        cast(avg(发车签收量) as decimal(18,2)) avgV,
                        isnull(SUM(发车签收量),0)+isnull(sum(背砂浆签收量),0) AS signV,
                        sum(转入方量) xV,
                        sum(a.退货量) backV,
                        sum(退货过磅量) rBackV, 
                        isnull(sum(发车实发数量),0)+isnull(sum(背砂浆签收量),0)  realV,
                        isnull(sum(发车设定量),0)+isnull(sum(背砂浆设定量),0)  setV,
                        avg(case when datediff(mi,出车时间,返厂时间) between 30 and 300 then datediff(mi,出车时间,返厂时间) else null end) mi                    
                    FROM cv_发货单_发货单 a 
                        LEFT JOIN cv_任务_任务内容 b on a.任务编号=b.任务编号 
                    WHERE   $conditions and 发车货物类型<>'水票' and isnull(a.是否已过磅,0)=1
                    GROUP BY b.合同编号,a.任务编号 WITH ROLLUP
                ) aa left join cv_任务_任务内容 cc on aa.tID=cc.任务编号 
                     left join cv_合同_合同内容 dd on aa.cID=dd.合同编号 
                order by aa.signV";
        }


        // echo $sql;
        // return;
        $datas = parent::gArrBySql(null,$sql);

        if (!isset($para["grp"])) {
            $sumSql = "SELECT'【总计】' pName, '' pPart, '' G,'' P,SUM(V) AS V,
                      SUM(c) AS c, SUM(xV) xV, SUM(backV) backV, 
                      SUM(rBackV) rBackV, SUM(realV) realV, 
                      SUM(signV) signV,
                      SUM(setV) AS setV
                FROM  [PV_Out] b
                WHERE $conditions";
            if (isset($para["debug"])) {
                echo $sumSql;
            }
            $sum = parent::gArrBySql(null,$sumSql);
            $subRec = $sum["Records"][0];
        } else {
            $subRec=[];
        }
        $results = $datas["Records"];
        echo  json_encode(["count" =>count($results),"pageCount"=>1,
            "results"=>$results,"sum"=>$subRec]);
    }
    // Delete
    public static function Delete( $class , $para  ) {
        echo '{"code":0,"res":"fail","affected":0,"msg":"Query modal not support Delete"}';
    }
    // U  update 更新
    public static function  Update($class,$id,$data){
        echo '{"code":0,"res":"fail","affected":0,"msg":"Query modal not support Update"}';
    }
}
