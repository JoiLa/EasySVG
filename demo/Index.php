<?php
    /**
     * Created by Liang
     * Email: 1144828910@qq.com
     * User: Administrator
     * Date: 2019-10-24
     * Time: 15:43
     */


    require_once( '../src/EasySVG.php' );

    try {
        $obj    = ( new EasySVG() );
        $result = $obj->CreateCaptcha();
        print_r( $result );
    } catch( Exception $e ) {
        print_r( $e->getMessage() );
    }