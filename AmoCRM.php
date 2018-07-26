<?php

class AmoCRM
{

    /**
    * Логин и хэш пользователя
    *
    * @var array
    */
    private $user = [
                    'USER_LOGIN' => 'the.isik.alexander@gmail.com',
                    'USER_HASH' => '849341f12ec3e075f267dcb8589e44b8428bbdb7'
                    ];
    
    /**
    * Субдомен для личного кабинета
    *
    * @var string
    */
    private $subdomain = 'theisikalexander';
    
    
    /**
    * Производим первичную авторизацию
    *
    * @return boolean
    */
    public function __construct ()
    {
        
        $link = 'https://' . $this->subdomain . '.amocrm.ru/private/api/auth.php?type=json';
        
        $curl = curl_init(); 

        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0' );
        curl_setopt( $curl, CURLOPT_URL, $link );
        curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode( $this->user ) );
        curl_setopt( $curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json'] );
        curl_setopt( $curl, CURLOPT_HEADER, false);
        curl_setopt( $curl, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt' );
        curl_setopt( $curl, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt' );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 0);
        
        $out = curl_exec( $curl );
        
        $code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
        curl_close($curl); 

        $code = (int) $code;
        
        $errors = [
                  301 => 'Moved permanently',
                  400 => 'Bad request',
                  401 => 'Unauthorized',
                  403 => 'Forbidden',
                  404 => 'Not found',
                  500 => 'Internal server error',
                  502 => 'Bad gateway',
                  503 => 'Service unavailable'
                ];
                
        $this->getException( $code, $errors );

        $response = $this->getResponse( $out );
        
        if( isset( $response['auth'] ) ) {
            
            return true;
            
        }
        
        return false;

    }
    
    public function getAllLeads ()
    {

        $link = 'https://' . $this->subdomain . '.amocrm.ru/api/v2/leads';

        $curl = curl_init();
        
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0' );
        curl_setopt( $curl, CURLOPT_URL, $link);
        curl_setopt( $curl, CURLOPT_HEADER, false);
        curl_setopt( $curl, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt' );
        curl_setopt( $curl, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt' ); 
        curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 0 );
        curl_setopt( $curl, CURLOPT_HTTPHEADER, ['IF-MODIFIED-SINCE: Mon, 01 Aug 2013 07:07:23']);
        
        $out = curl_exec( $curl );
        $code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
 
        curl_close( $curl );
        
        $code = (int) $code;
        $errors = [
                  301=>'Moved permanently',
                  400=>'Bad request',
                  401=>'Unauthorized',
                  403=>'Forbidden',
                  404=>'Not found',
                  500=>'Internal server error',
                  502=>'Bad gateway',
                  503=>'Service unavailable'
                ];
          
        $this->getException( $code, $errors );
        
        return $this->getResponse( $out );;
        
    }
    
    /**
    * Получаем сделки без задач
    *
    * @return array
    */
    public function getEmptyLeads ( )
    {
        
        $leads = $this->getAllLeads();
        
        if ( !empty( $leads ) ) {
        
            foreach ( $leads as $lead ) {
                
                if ( $lead['closest_task_at'] == 0 ) {
                    
                    $emptyLeads[] = (array) $lead;
                    
                }
                
            }

            return $emptyLeads;
        
        }
        
        return false;
    }
    
    /**
    * Добавляем задания в пустые сделки
    *
    * @return boolean
    */
    public function addNewTask ()
    {
        
        $emptyLeads = $this->getEmptyLeads();
        
        $add = [];
        
        foreach ( $emptyLeads as $lead ) {
            
            
            $add[] = [
                        'element_id' => $lead['id'],
                        'element_type' => 2,
                        'task_type' => 1,
                        'text' => 'Сделка без задачи',
                        'responsible_user_id' => $lead['responsible_user_id'],
                        'complete_till_at' => $lead['updated_at']
                    ];
                                
            
            
        }

        $tasks['add'] = (array) $add;

        $link = 'https://' . $this->subdomain . '.amocrm.ru/api/v2/tasks';
        
        $curl = curl_init();
        
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0' );
        curl_setopt( $curl, CURLOPT_URL, $link );
        curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'POST' );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, json_encode($tasks) );
        curl_setopt( $curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json') );
        curl_setopt( $curl, CURLOPT_HEADER, false );
        curl_setopt( $curl, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt' );
        curl_setopt( $curl, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt' );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 0 );
        
        $out = curl_exec( $curl );
        $code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
        
        $code = (int) $code;
        
        $errors = [
          301=>'Moved permanently',
          400=>'Bad request',
          401=>'Unauthorized',
          403=>'Forbidden',
          404=>'Not found',
          500=>'Internal server error',
          502=>'Bad gateway',
          503=>'Service unavailable'
        ];
        
        $this->getException( $code, $errors );
        
        return true;
        
    }
    
    /**
    * Проверяем, имеются ли ошибки
    *
    * @return boolean
    */
    public function getException ( $code, $errors )
    {
        
        try
        {
          
            if( $code != 200 && $code != 204 ) {
                
                throw new \Exception( isset( $errors[$code] ) ? $errors[$code] : 'Undescribed error', $code );
                
            }
            
        }
        catch( \Exception $e)
        {
            
            die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode() );
          
        }
        
    }
    
    /**
    * Получить ответ
    *
    * @return array
    */
    public function getResponse ( $out )
    {
        
        $response = json_decode( $out, true );
        $response = $response['_embedded']['items'];
        
        return $response;
        
    }
    
}

$amoCRM = new AmoCRM();

if ( !empty( $amoCRM->getEmptyLeads() ) ) {
    
    $amoCRM->addNewTask();
    
}
