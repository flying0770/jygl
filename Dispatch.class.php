<?php
/**
 * Created by PhpStorm.
 * User: zanblo
 * Date: 2017-05-29
 * Time: 11:39
 */

require_once 'Common.class.php';
require_once 'funs.php';
// 进货查询

class Dispatch extends Common
{
     public static $colums='*';
     static $tablname='PV_Deliver';
     static $fldSort='';
     static $ID='发货单编号';
     // Read 获取某ID的记录内容
     public static function Read( $class , $id )
     {
         if (parent::$debug_mode){
             echo '动态加载 $class.单条选择.$id'.'</br>';
         }
         $database = self::getDB();
         switch ($id)   
         {
             case "CarList":
                 self::getCarStatList();
                 break;
             case "CarList_E":
                 self::getCarStatList_E();
                 break;
             default:
                 $arr=explode('_',$id);
                 switch ( $arr[0] )
                 {
                     case 'Task':
                         self::getTaskCarList( $arr[1] );
                         break;
                     case 'Car':
                         self::getCarState( $arr[1] );
                         break;
                     case 'Driver':
                         self::getDriverState( $arr[1] );
                         break;
                     case 'DtoC':    //司机换车  driver to(change) car
                         //$sql=" exec TDriverChangeCar null,'$arr[1]','$arr[2]'  ";
                         self::changeCarForDriver( $arr[1],$arr[2]);
                         break;
                     default:
                 }
         }
         return;
         if (self::$debug_mode) {
             echo $sql;
         }
         $sql = (iconv('UTF-8','GBK',$sql));
         // 第一个记录集为数据
         $stmt =$database->query($sql);
         $datas = $stmt->fetchAll(PDO::FETCH_ASSOC) ;
         // 第二个记录集为统计
         $stmt->nextRowset();
         $stats =$stmt->fetch(PDO::FETCH_ASSOC);

         $stmt->closeCursor();
         $database = null;

         echo  json_encode(Funs::auto_charset(["count" =>$stats["Counts"],"pageCount"=>1,"results"=>$datas]));
     }

     // New option 获取添加客户时，应该传过去的选项
     public static function NewOpts( $class,$ver){
        $curVer = 1.0001;    // 当前版本
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
        $database = parent::getProDB();
        $heads = getallheaders();
        $userName = isset($heads["Username"])?$heads["Username"]:"";
 
        $j = json_decode($data,true);
        $sn = rand(1000000,9999999);
        $sql = " exec [dbo].[PP_FHD]
                @发货单编号 = '新发货单',
                @任务编号 =N'".$j['taskId']."',
                @搅拌站 = N'".$j['st']."',
                @调度员 = N'$userName',
                @质检员 = N'".$j['inspector']."',
                @司磅员 = N'',
                @内部车号 = N'".$j['car']."',
                @司机 = N'".$j['driver']."',
                @浇注方式 = N'".$j['pour']."',
                @泵车号 =  N'".$j['pump']."',
                @货物类型 = N'".$j['hType']."',

                @主机操作 = N'".$j['oper']."',
                @现场调度 = N'".$j['spotDispatcher']."',
                @发车方量 =".$j['V'].",
                @背砂浆 = 0,
                @减差 = 0,
                @调度增量 = 0,
                @注释 = '',
                @司机提示 = '',
                @调度发车提示 = '',
                @泵车派车单号 = '',
                @打印提示 = '',
                @货物类型分类 ='',
                @自动播放 = 1,
                @接洗泵水标志 = 0,
                @调度发车带拖泵标志 =0,
                @调度发车不发往搅拌站标志 =0,
                @允许磅房修改方量标志 =0,
                @是否打印票据 = 1,
                @打印机名称 = '',
                @锁定方量标志 = 0,
                @录入人 = N'".$userName."',
                @录入端 = N'".$userName."',
                @发车方式 = N'生成发货单',
                @发车端 = N'app',
                @sql='',
                @replaceName='',
                @sn =  N'".$sn."'";
                    // @生产提示 =  N'".$j['notice']."',
                // echo $sql;
                // return;
                
        $sql = (iconv('UTF-8', 'GBK', $sql));
        
        try
        {
            $res = $database->pdo->exec($sql);
            $sql = "select * from 系统_时间戳 where sn='$sn'";
            $sql = (iconv('UTF-8','GBK',$sql));
            $res = $database->query($sql)->fetchAll(PDO::FETCH_ASSOC) ;
            $res = Funs::auto_charset($res);
            // var_dump($res);
            if (count($res)>0 && $res[0]['sn']==$sn && $res[0]['code']==0) {
                // $sql = "delete from 系统_时间戳 where sn='$sn'";
                // $sql = (iconv('UTF-8','GBK',$sql));
                // $database->query($sql );
                echo '{"code":0,"res":"sucess","affected":"1","msg":"发车成功，发货单编号为：'.$res[0]['result'].'"}';
            } else {
                // $sql = "delete from 系统_时间戳 where sn='$sn'";
                // $sql = (iconv('UTF-8','GBK',$sql));
                // $database->query($sql );
                echo '{"code":4,"res":"fail","affected":"1","msg":"发车时发生以下错误:'.($res[0]['msg']).'"}';
            }
        }
        catch (PDOException $e)
        {
            $msg = $e->getMessage();
            $sql = "delete from 系统_时间戳 where sn='$sn'";
            $sql = (iconv('UTF-8','GBK',$sql));
            $database->query($sql );
            echo '{"code":3,"res":"fail","affected":"0","msg":'.$msg.'}';
        }
     }
     // List
     public static function Lists( $class , $para )
     {
         if (parent::$debug_mode) echo "动态加载 $class.列表"."</br>";
         $database = self::getDB();

         $sql="select a.任务编号, a.书面任务编号, a.具体工程名称 as 工程名称, a.具体施工单位 as 施工单位, a.砼强度等级 as 强度, a.砼数量 as 需供, b.完成 as 完成, b.车数 as 车数, 
            a.浇注方式,a.泵车,dbo.getDeliverList(a.任务编号) carlist from cv_调度_任务单 a left join cv_任务_任务累计 b on a.任务编号 = b.任务编号
               select @@rowcount Counts,1 pageCount";
         parent::getContentBySql( $database,$sql);
     }
     // Delete
     public static function Delete( $class , $para  ) {
      echo '{"code":0,"res":"fail","affected":0,"msg":"Query modal not support Delete"}';
     }
     // U  update 更新
     public static function Update($class,$id,$data){
      echo '{"code":0,"res":"fail","affected":0,"msg":"Query modal not support Update"}';
     }

     // 获取车辆状态列表
    public static function getCarStatList() {
        $sql="select *,
            's' + case when isnull(生产状态,'') = '开始生产' then  '1' else '0' end   +
                  case when isnull(生产状态,'') = '生产完毕' then  '1' else '0' end   +
                  case when isnull(是否已过磅,0)=1 then '1' else '0' end +'0' icon,
            case when isnull(发货单编号,'')='' then '待发车' else
                     case when isnull(是否已过磅,0)=1 then '拉运中' else
                     case  isnull(生产状态,'') when '开始生产' then '生产中'
                                                when '生产完毕' then '待过磅'
                                                else '待生产' end end end state
            from cv_调度_车辆调度 order by 是否已过磅,出车时间,生产状态,待发序号
            select @@rowcount Counts,1 pageCount
        ";
        parent::getContentBySql( null,$sql);
    }

    // 获取车辆状态列
    public static function getCarStatList_E() {
        $sql="select aa.内部车号 car,aa.发货单编号 fId,aa.出车时间 dt,bb.发车单车数量 V,aa.搅拌站 std,
               's' + case when isnull(aa.生产状态,'') = '开始生产' then  '1' else '0' end   +
                      case when isnull(aa.生产状态,'') = '生产完毕' then  '1' else '0' end   +
                      case when isnull(aa.是否已过磅,0)=1 then '1' else '0' end +'0' icon,
               case when isnull(aa.发货单编号,'')='' then 
                        case when isnull(排序状态,'')='等待发车' then 
                         'waiting' else 'paused' end
                    else
                         case when isnull(aa.是否已过磅,0)=1 then 'weighted' else
                         case  isnull(aa.生产状态,'') when '开始生产' then 'producting'
                                                    when '生产完毕' then 'producted'
                                                    else  'billed' end end end state
              from cv_调度_车辆调度 aa left join cv_发货单_发货单 bb on aa.发货单编号 = bb.发货单编号 order by aa.是否已过磅,aa.搅拌站显示序号,aa.待发序号,aa.出车时间
            select ".TMH_STATIONS_COUNT." stdCount,@@rowcount Counts,1 pageCount";
        parent::getContentBySql( null,$sql,0);
    }


    public static function getCarState( $carId ) {
        $sql="select a.*,b.工程名称,b.施工单位,b.施工地点, b.施工部位, b.运距,   b.强度等级, b.联系电话,b.备注,c.皮重,c.回皮时间,b.坍落度,
                                's' + case when isnull(生产状态,'') = '开始生产' then  '1' else '0' end   +
                                       case when isnull(生产状态,'') = '生产完毕' then  '1' else '0' end   +
                                       case when isnull(是否已过磅,0)=1 then '1' else '0' end +'0' icon,
                                 case when isnull(发货单编号,'')='' then '待发车' else
                                 case when isnull(是否已过磅,0)=1 then '拉运中' else
                                 case  isnull(生产状态,'') when '开始生产' then '生产中'
                                                            when '生产完毕' then '待过磅'
                                                            else '待生产' end end end state,
                                  case when 待发序号>0 then dbo.getPreWaitListForCar(a.待发序号) end preWait
                                from cv_调度_车辆调度 a left join pv_task b on a.任务编号=b.任务编号 left join cv_调度_车辆信息 c on a.内部车号=c.内部车号 where a.内部车号='$carId'";
        parent::getContentBySql( null,$sql);
    }

    public static function getDriverState( $driver ) {
        $sql="select a.*,b.工程名称,b.施工单位,b.施工地点, b.施工部位, b.运距,   b.强度等级, b.联系电话,b.备注,c.皮重,c.回皮时间,b.坍落度,
                                's' + case when isnull(生产状态,'') = '开始生产' then  '1' else '0' end   +
                                       case when isnull(生产状态,'') = '生产完毕' then  '1' else '0' end   +
                                       case when isnull(是否已过磅,0)=1 then '1' else '0' end +'0' icon,
                                 case when isnull(发货单编号,'')='' then '待发车' else
                                 case when isnull(是否已过磅,0)=1 then '拉运中' else
                                 case  isnull(生产状态,'') when '开始生产' then '生产中'
                                                            when '生产完毕' then '待过磅'
                                                            else '待生产' end end end state,
                                  case when 待发序号>0 then dbo.getPreWaitListForCar(a.待发序号) end preWait
                                from cv_调度_车辆调度 a left join pv_task b on a.任务编号=b.任务编号 left join cv_调度_车辆信息 c on a.内部车号=c.内部车号 where a.排序司机='$driver' or a.司机='$driver'";
        parent::getContentBySql( null,$sql);
    }

    /**
     * 
     * @param  [type] $taskId [description]
     * @return [type]         [description]
     */
    public static function getTaskCarList( $taskId ) {
        $sql = "
                SELECT 发车货物类型 +case when isnull(背砂浆标志,0)=1 then '+砂浆' else '' end  hType,
                    aa.发货单编号 fId,
                    内部车号 as car,
                    司机 drv,
                    出车时间 tm,
                    isnull(发车单车数量,0)+isnull(背砂浆单车数量,0) as V,
                    isnull(发车实发数量,0)+isnull(背砂浆实发数量,0) as realV,
                    isnull(发车签收量,0)+isnull(背砂浆签收量,0) as signV,
                    注释 as note,
                    aa.返厂时间 as backTm,
                    aa.退货量 backV,
                    aa.发车设定量 as setV from cv_发货单_发货单 aa where 是否已过磅=1 and 任务编号='$taskId'
                union
                select 
                    null htype,
                    '合计' fId,
                    cast(count(all 内部车号) as varchar(5))+'车' car,
                    '车:'+cast(convert(decimal(5,2),avg(all 发车签收量)) as varchar(5)) drv,
                    null tm,
                    sum(isnull(发车单车数量,0)+isnull(背砂浆单车数量,0)) as V,
                    sum(isnull(发车实发数量,0)+isnull(背砂浆实发数量,0)) as realV, 
                    sum(isnull(发车签收量,0)+isnull(背砂浆签收量,0)) as signV,
                    cast(avg(datediff(minute,出车时间,返厂时间)) as varchar(5)) note,
                    null backTm,
                    sum(aa.退货量) backV,
                    sum(发车设定量) setV from cv_发货单_发货单 aa where 是否已过磅=1 and 任务编号='$taskId'
                order by aa.发货单编号 asc
                select @@rowcount Counts,1 pageCount
        ";
        parent::getContentBySql( null,$sql);
    }

    public static function changeCarForDriver( $driver,$carId ) {
        $sql = " exec TDriverChangeCar null,'$driver','$carId'  ";
        parent::getContentBySql( null,$sql);
    }

    public static function getDriverStaticsByRange($st,$ed )
    {
        $dateSql = "";
        if (Funs::checkDateIsValid($st) && Funs::checkDateIsValid($ed)) {
            $dateSql = " 出车时间 between  dbo.getDayStart('司机','$st') and dbo.getDayEnd('司机','$ed') ";
        } else {
            $dateSql = " 出车时间 between  dbo.getDayStart('司机',getdate()) and dbo.getDayEnd('司机',getdate())";
        }

        $sql ="select  司机,count(发货单编号) 车数,sum(签收量) 方量, avg(运距) 运距,SUBSTRING('■■■■■■■■■■■■■■■■■■■■■■■■■',1,count(发货单编号)) 趟次  from v通用_发货单 where $dateSql group by 司机 order by 车数";
        parent::getContentBySql( null,$sql);
    }

    public static function getDriverStaticsByDay($day){
        $dateSqls=[
            "Today" =>" 出车时间 between  dbo.getDayStart('司机',getdate()) and dbo.getDayEnd('司机',getdate()) ",
            "Yesterday"=> " 出车时间 between  dbo.getDayStart('司机',dateadd(d,-1,getdate())) and dbo.getDayEnd('司机',dateadd(d,-1,getdate())) ",
            "Tomorrow"=>" 出车时间 between  dbo.getDayStart('司机',dateadd(d,1,getdate())) and dbo.getDayEnd('司机',dateadd(d,1,getdate()))"
        ];
        $dateSql=$dateSqls[array_key_exists($day,$dateSqls)?$day:"Today"];
        //$dateSql=" 出车时间 between  '2017-04-11 00:00:00' and '2017-04-12 00:00:00'";
        $sql ="select  司机,count(发货单编号) 车数,sum(签收量) 方量, avg(运距) 运距,SUBSTRING('■■■■■■■■■■■■■■■■■■■■■■■■■',1,count(发货单编号)) 趟次  from v通用_发货单 where $dateSql group by 司机 order by 车数";

        parent::getContentBySql( null,$sql);
    }
    /*
        获取 泵车统计 按时间段
     */
    public static function getPumpStaticsByRange($st,$ed ) {
        $dateSql = "";
        if (Funs::checkDateIsValid($st) && Funs::checkDateIsValid($ed)) {
            $dateSql = " 出车时间 between  dbo.getDayStart('司机','$st') and dbo.getDayEnd('司机','$ed') ";
        } else {
            $dateSql = " 出车时间 between  dbo.getDayStart('司机',getdate()) and dbo.getDayEnd('司机',getdate())";
        }

        $sql ="select  泵车司机,count(发货单编号) 车数,sum(签收量) 方量, avg(运距) 运距,SUBSTRING('■■■■■■■■■■■■■■■■■■■■■■■■■',1,count(发货单编号)) 趟次  from v通用_发货单 where $dateSql group by 泵车司机 order by 车数";
        parent::getContentBySql( null,$sql);
    }
    /*
        获取 泵车统计 按日起
     */
    public static function getPumpStaticsByDay($day){
        $dateSqls=[
            "Today" =>" 出车时间 between  dbo.getDayStart('司机',getdate()) and dbo.getDayEnd('司机',getdate()) ",
            "Yesterday"=> " 出车时间 between  dbo.getDayStart('司机',dateadd(d,-1,getdate())) and dbo.getDayEnd('司机',dateadd(d,-1,getdate())) ",
            "Tomorrow"=>" 出车时间 between  dbo.getDayStart('司机',dateadd(d,1,getdate())) and dbo.getDayEnd('司机',dateadd(d,1,getdate()))"
        ];
        $dateSql=$dateSqls[array_key_exists($day,$dateSqls)?$day:"Today"];
        //$dateSql=" 出车时间 between  '2017-04-11 00:00:00' and '2017-04-12 00:00:00'";
        $sql ="select 泵车司机,count(发货单编号) 车数,sum(签收量) 方量, SUBSTRING('■■■■■■■■■■■■■■■■■■■■■■■■■',1,cast(ceiling(sum(签收量)/100) as int)) 趟次  from v通用_发货单 where $dateSql and 签收量>0  group by 泵车司机 order by 车数";
        parent::getContentBySql( null,$sql);
    }

    /**
     * 获取罐车出车统计
     * @param  [type] $st [description]
     * @param  [type] $ed [description]
     * @return [type]     [description]
     */
    public static function getCarStaticsByRange($st,$ed ) {
        $dateSql = "";
        if (Funs::checkDateIsValid($st) && Funs::checkDateIsValid($ed)) {
            $dateSql = " 出车时间 between  dbo.getDayStart('司机','$st') and dbo.getDayEnd('司机','$ed') ";
        } else {
            $dateSql = " 出车时间 between  dbo.getDayStart('司机',getdate()) and dbo.getDayEnd('司机',getdate())";
        }

        $sql ="select 车号,count(发货单编号) 车数,sum(签收量) 方量, SUBSTRING('■■■■■■■■■■■■■■■■■■■■■■■■■',1,cast(ceiling(sum(签收量)/100) as int)) 趟次  from v通用_发货单 where $dateSql and 签收量>0  group by 车号 order by 车数";
        parent::getContentBySql( null,$sql);
    }

    public static function getCarStaticsByDay($day)  {
        $dateSqls=[
            "Today" =>" 出车时间 between  dbo.getDayStart('司机',getdate()) and dbo.getDayEnd('司机',getdate()) ",
            "Yesterday"=> " 出车时间 between  dbo.getDayStart('司机',dateadd(d,-1,getdate())) and dbo.getDayEnd('司机',dateadd(d,-1,getdate())) ",
            "Tomorrow"=>" 出车时间 between  dbo.getDayStart('司机',dateadd(d,1,getdate())) and dbo.getDayEnd('司机',dateadd(d,1,getdate()))"
        ];
        $dateSql=$dateSqls[array_key_exists($day,$dateSqls)?$day:"Today"];
        $sql ="select  车号,count(发货单编号) 车数,sum(签收量) 方量, avg(运距) 运距,SUBSTRING('■■■■■■■■■■■■■■■■■■■■■■■■■',1,count(发货单编号)) 趟次  from v通用_发货单 where $dateSql group by 车号 order by 车数";
        parent::getContentBySql( null,$sql);
    }

    public static function getProjectStaticsByRange($st,$ed ) {
        $dateSql = "";
        if (Funs::checkDateIsValid($st) && Funs::checkDateIsValid($ed)) {
            $dateSql = " 出车时间 between  dbo.getDayStart('司机','$st') and dbo.getDayEnd('司机','$ed') ";
        } else {
            $dateSql = " 出车时间 between  dbo.getDayStart('司机',getdate()) and dbo.getDayEnd('司机',getdate())";
        }
        $sql ="select  具体工程名称,具体施工单位,count(发货单编号) 车数,sum(签收量) 方量, avg(运输时间) 用时  from v通用_发货单 where $dateSql group by 具体工程名称,具体施工单位 order by 具体工程名称,具体施工单位";
        parent::getContentBySql( null,$sql);
    }

    public static function getProjectStaticsByDay($day)  {
        $dateSqls=[
            "Today" =>" 出车时间 between  dbo.getDayStart('司机',getdate()) and dbo.getDayEnd('司机',getdate()) ",
            "Yesterday"=> " 出车时间 between  dbo.getDayStart('司机',dateadd(d,-1,getdate())) and dbo.getDayEnd('司机',dateadd(d,-1,getdate())) ",
            "Tomorrow"=>" 出车时间 between  dbo.getDayStart('司机',dateadd(d,1,getdate())) and dbo.getDayEnd('司机',dateadd(d,1,getdate()))"
        ];
        $dateSql=$dateSqls[array_key_exists($day,$dateSqls)?$day:"Today"];
        $sql ="select  具体工程名称,具体施工单位,count(发货单编号) 车数,sum(签收量) 方量, avg(运输时间) 用时  from v通用_发货单 where $dateSql group by 具体工程名称,具体施工单位 order by 具体工程名称,具体施工单位";
        parent::getContentBySql( null,$sql);
    }

    public static function getTodaySum(){
        $sql = "    
            declare @出货日_统计_起时间 varchar(20)
            declare @出货日 varchar(10)
            select @出货日_统计_起时间 = isnull(值,'') from cv_系统_参数设置 WHERE (项目 = '出货日_统计_起时间')
            
            --select @出货日_统计_起时间,convert(varchar(10),getdate()-@出货日_统计_起时间,121)+' '+@出货日_统计_起时间
            select @出货日= convert(varchar(10),getdate()-@出货日_统计_起时间,121)
            select  'today' d,@出货日 dt,sum(isnull(发车签收量,0)+isnull(背砂浆签收量,0)) outSum FROM cv_发货单_发货单 
            where  出车时间> @出货日+' '+@出货日_统计_起时间
        ";
        parent::getContentBySql(parent::getDB(), $sql);
    }

    /*获取所有反馈信息*/
    public static function feedbackList($phone){
        $sql = "select invoiceParam,T_submit,checked from driver_司机事务 where phone = '$phone' and reasonType = '发货单错误' and T_submit BETWEEN DATEADD(mm, - 1, GETDATE()) AND GETDATE()";
        // echo $sql;
        parent::getContentBySql(parent::getProDB(), $sql);   
    }

    /*获取泵车信息*/
    public static function getPumpList(){
        $sql = "select 内部车号 car from cv_调度_车辆信息 where 类型='泵车'";
        // echo $sql;
        parent::getContentBySql(parent::getProDB(), $sql); 
    }

    /*获取 某个车辆相关的司机*/
    public static function getDrvListByCar($para){
      $chk = Funs::inject_checks($para);
      if ( $chk==="" ){
        $car =  isset($para['car'])?$para['car']:'';
        $sql = "select 司机 drv from cv_调度_出货车辆与司机 where 内部车号='$car'";
        parent::getContentBySql(parent::getProDB(), $sql); 
      } else {
        echo '{"err":"'.$chk.'","count":0,pageCount":1,"results":[]}';
      }   
    }

    /*获取 主机操作*/
    public static function getMixerOperList($para){
        $chk = Funs::inject_checks($para);
        if ( $chk==="" ){
            $st=  isset($para['st'])?$para['st']:'';
            $sql = "select 内容 oper from 通用_选项明细 where 项目='主机操作'";
            parent::getContentBySql(parent::getProDB(), $sql);  
        } else{
            echo '{"err":"'.$chk.'","count":0,pageCount":1,"results":[]}';
        }
    }

    /*获取 质检员*/
    public static function getInspectorList(){
        $sql = "select 内容 inspector from 通用_选项明细 where 项目='质检员'";
        parent::getContentBySql(parent::getProDB(), $sql);  
    }

    /*获取 现场调度*/
    public static function getSpotDispatcherList(){
        $sql = "select 内容 xcdd from 通用_选项明细 where 项目='现场调度'";
        parent::getContentBySql(parent::getProDB(), $sql);  
    }

    /*获取待发车辆*/
    public static function getWaitingList(){
        $sql = "select 内部车号  car from cv_调度_车辆调度 where 排序状态='等待发车' order by 返回时间 ";
        parent::getContentBySql(parent::getProDB(), $sql);  
    }

    public static function getDispatchList($para){
        $busniess_id = $para["busniess_id"];
        $sql = "select distinct a.任务编号, 具体工程名称, 具体施工单位, 具体建设单位, 施工部位, 砼强度等级, 浇注方式, 砼数量, b.完成, b.车数 from cv_调度_任务单 a left join cv_调度_任务单合计 b on a.任务编号=b.任务编号 where a.合同编号 = '$busniess_id' and 进行情况 = '正在进行' group by a.任务编号,具体工程名称,具体施工单位, 具体建设单位, 施工部位, 砼强度等级, 浇注方式, 砼数量, b.完成, b.车数";
        parent::getContentBySql(parent::getDB(), $sql);
    }

    /**
     * [根据泵车号获取泵车所在所有工地]
     * @param  [type] $para [泵车号]
     * @return [type]       [工地列表]
     */
    public static function getTaskByPump($para){
        $item = $para["pump"];

        $sql = "select * from cv_任务_任务内容 where 任务编号 in (select distinct 任务编号 from cv_调度_车辆调度 where 泵车号 = '$item')";

        parent::getContentBySql(parent::getProDB(), $sql);
    }


    /**
     * [删除发货单]
     * @param 
     *      
     */
    public static function cancelDispatch($para){
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            echo '{"code":1,"res":"fail","msg":"不支持GET方式提交"}';
        }
        $db = parent::getProDB();
        $heads = getallheaders();
        $userName = $heads["Username"] ?? "";
        if(is_string($para)){
            $j = json_decode($para,true);
        } else {
            $j= $para;
        }
        if(!isset($para["fId"])){
            echo '{"code":2,"res":"fail","msg":"丢失参数。"}';
        }
        $sn = rand(1000000,9999999);
        $sql = " exec [dbo].[cheXiaoChuChe]
                    @发货单编号 = N'" . $para["fId"] . "',
                    @用户名称 =  N'$userName',
                    @sn =  N'" . $sn ."'";
         // echo $sql;
         //    return;
        $sql = (iconv('UTF-8', 'GBK', $sql));

        try
        {
            $res = $db->pdo->exec($sql);
            $sql = "select * from 系统_时间戳 where sn='$sn'";
            $sql = (iconv('UTF-8','GBK',$sql));
            $res = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ;
            $res = Funs::auto_charset($res);
            // var_dump($res);
            if (count($res)>0 && $res[0]['sn']==$sn && $res[0]['code']==0) {
                echo '{"code":0,"res":"sucess","affected":"1","msg":"撤销成功，发货单编号为：'.$res[0]['result'].'"}';
            } else {
                echo '{"code":4,"res":"fail","affected":"1","msg":"撤销时发生以下错误:'.($res[0]['msg']).'"}';
            }
        }
        catch (PDOException $e)
        {
            $msg = $e->getMessage();
            $sql = "delete from 系统_时间戳 where sn='$sn'";
            $sql = (iconv('UTF-8','GBK',$sql));
            $db->query($sql );
            echo '{"code":3,"res":"fail","affected":"0","msg":'.$msg.'}';
        }
    }
}
