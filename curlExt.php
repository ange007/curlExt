<?php
/**
 * curlExt - класс расширения для cURL.
 *
 * @author
 * Vladimir B.
 * 
* Date: 03.05.2014
 */

class curlExt {
    
    /* ToDo:   
     * - Сделать распределение кук по доменам
     * 
     */
    
    var $userAgent;
    var $cookies;
    var $lastPage;
    
    public function curl( ) 
	{
        require_once ROOT . '/classes/randomUA.php';
		
		$this->userAgent = $this->getUserAgent( );
        $this->lastPage = '';
        $this->cookies = '';
    }
    
	private function getUserAgent( ) 
	{
		$randomUserAgent = new randomUserAgent( );
		$userAgent = $randomUserAgent->getUserAgent( );
		unset( $randomUserAgent );

		return $userAgent;
	}

	private function toQueryParams( $arr, $add = null ) 
	{
		foreach( $arr as $k => $v ) 
		{
			$result .= $k . '=' . $v . '&';
		}

		$result = substr($result, 0, -1);
		if( $add ) { $result .= '&' . $add; }

		return $result;
	}

	// Добавление куки
	public function addCookie( $name, $value )
	{
		if( trim( $value ) == 'deleted' ) { $value = ''; }
		$this->cookies[ trim( $name ) ] = trim( $value );
		
		return $this->getCookies( );
	}
	
	// Изъятие кукисов из запроса
	private function extractCookies( $inputCookies )
	{
		$extractedCookies = array( );
		preg_match_all( '#Set-Cookie: (.{1,100}?)=(.{1,100}?);#is', $inputCookies, $extractedCookies );
		
		for( $i = 0; $i < count( $extractedCookies[1] ); $i++ ) 
		{
			$this->addCookie( $extractedCookies[1][$i], $extractedCookies[2][$i] );
		}
		
		return $this->getCookies( );
	}	
	
	// Получить строку со списком кукисов для отправки
	private function getCookies( )
	{
		if( empty( $this->cookies) ) { return ''; }
		
		$resultString = '';
		foreach( $this->cookies as $key => $value )
		{
			$resultString .= trim( $key ) . '=' . trim( $value ) . '; ';
		}
		
		return $resultString;
	}	
	
	// Запрос страницы
    public function getPage( $url, $post = null, $xRequested = null, $charsetDecode = null  ) 
	{
		if( !empty( $post ) && is_array( $post ) )	{ $post = $this->toQueryParams( $post ); }

		$location = $url;
		$redirectCount = 0;		
		
		while( $redirectCount < 6 && $location != '' )
		{
			$textResult = $this->sendQuery( $location, $post, $this->getCookies( ), false, false, $this->lastPage, $this->userAgent, $xRequested );

			// Записываем текущие данные
			$this->lastPage = $location;
			$this->extractCookies( $textResult );
			
			/*
			http://stackoverflow.com/questions/5142869/how-to-remove-http-headers-from-curl-response
			list( $header, $contents ) = preg_split( '/([\r\n][\r\n])\\1/', curl_exec( $ch ), 2 );
			$status = curl_getinfo( $ch );*/
			
			// Обрабатываем редирект
			$matches = array( );
			if( preg_match( '#HTTP/1\.[01]\s(301|302|303|307).*?Location:\s(.*?)\s#isu', $textResult, $matches ) )
			{
				$redirectCount++;
				$location = $matches[2];

				if( defined( 'DEBUG' ) && DEBUG == true ) { echo '<br />curl редирект: ' . $location; }		
				
				if( $location == '/' ) { break; }
					else { continue; }
			}
			else { break; }
		}
        
		$textResult = preg_replace( '#^(.*?)(<(.*?))$#isu', '$2', $textResult );
        return ( !empty( $charsetDecode ) ? iconv( $charsetDecode . '//IGNORE', 'utf-8', $textResult ) : $textResult );
    }

	// Отправка запроса
	public function sendQuery( $query, $post = null, $cookies = null, $proxy = null, 
								$follow = null, $referer = null, $userAgent = null, 
								$xRequested = null ) 
	{
		$curl = curl_init( );
		if( !empty( $referer ) ) { $referer = $query; }
		
		if( defined( 'DEBUG' ) && DEBUG == true ) { echo '<br />curl запрос: ' . $query . ( !empty( $post ) ? ( '?' . $post ) : '' ); }

		curl_setopt( $curl, CURLOPT_URL, $query );
		curl_setopt( $curl, CURLOPT_HEADER, ( ( empty( $getHeader ) || $getHeader == true ) ? true : false ) );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, array(
				'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
				'Accept-Language: ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
				'Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7',
				'DNT: 1',
				( !empty( $xRequested ) && $xRequested == true ) ? 'X-Requested-With: XMLHttpRequest' : ''
		) );
		curl_setopt( $curl, CURLOPT_ENCODING, 'gzip,deflate' );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 10 );
		curl_setopt( $curl, CURLOPT_TIMEOUT, 10 );
		
		if( substr_count( $query, 'https://' ) )
		{
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, 0 );
			curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 0 );
		}
		
		if( !empty( $post ) ) 
		{
			curl_setopt( $curl, CURLOPT_POST, 1 );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, $post );
		}
		if( !empty( $cookies ) ) { curl_setopt( $curl, CURLOPT_COOKIE, $cookies ); }
		if( !empty( $referer ) ) { curl_setopt( $curl, CURLOPT_REFERER, $referer ); }
		if( !empty( $userAgent ) ) { curl_setopt( $curl, CURLOPT_USERAGENT, $userAgent ); }
		if( !empty( $proxy ) ) { curl_setopt( $curl, CURLOPT_PROXY, $proxy ); }
		if( !empty( $follow ) && $follow == true ) { curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, 1 ); }

		$textResult = curl_exec( $curl );
		
		$curlError = curl_error( $curl );
		if( !empty( $curlError ) ) { echo '<br/>Ошибка при запросе: ' . $curlError; } 
		curl_close( $curl );

		//$textResult = unicode_decode( $textResult );
		//$textResult = iconv( 'cp1251//IGNORE', 'utf-8', $textResult ); // Преобразовываем по необходимости cp1251 в utf-8

		return htmlspecialchars_decode( $textResult );
	}
}