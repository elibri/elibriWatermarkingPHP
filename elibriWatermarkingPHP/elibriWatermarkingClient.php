<?php

//! @defgroup exceptions Wyjątki
//! @{
//! Lista wyjątków, które mogą zostać wygenerowane podczas połączenia z serwerem.
//! @}

//! @brief Wyjątek używany w przypadku wystąpienia błędu połączenia z serwerem
//! @ingroup exceptions
class ElibriAPIConnectionException extends Exception {

  //! konstruktor wyjątku w przypadku błędu zwróconego przez curl-a
  function __construct($msg, $errno) {
    parent::__construct($msg, $errno);
  }
}

//! @brief Wyjątek - Podane zostały błędne parametry
//! @ingroup exceptions
class ElibriParametersError extends Exception {

  function __construct($msg) {
    parent::__construct($msg, 400);

  }
}


//! @brief Wyjątek - brak autoryzacji
//! @ingroup exceptions
class ElibriInvalidAuthException extends Exception {

  function __construct() {
    parent::__construct("Unauthorized", 401);

  }
}

//! @brief Wyjątek po stronie serwera (Internal server error)
//! @ingroup exceptions
class ElibriServerErrorException extends Exception {

  function __construct() {
    parent::__construct("Server Error", 500);
  }
}


//! @brief Wyjątek po stronie serwera (Forbidden)
//! @ingroup exceptions
class ElibriForbiddenException extends Exception {
  function __construct($msg) {
    parent::__construct($msg, 403);
  }
}

//! @brief Wyjątek po stronie serwera (Request Expired) - źle ustawiony czas lokalnie
//! @ingroup exceptions
class ElibriRequestExpiredException extends Exception {
  function __construct() {
    parent::__construct("Request Expired", 408);
  }
}


//! @brief Wyjątek - Nieprawidłowy login lub hasło
//! @ingroup exceptions
class ElibriWrongFormatsException extends Exception {

  function __construct() {
    parent::__construct("Wrong Format, allowed: 'epub', 'mobi', 'epub,mobi', 'mp3_in_zip'", 1000);
  }
}

//! @brief Wyjątek - Nieprawidłowy login lub hasło
//! @ingroup exceptions
class ElibriNotFoundException extends Exception {

  function __construct() {
    parent::__construct("Invalid url or http method", 404);
  }
}

//! @brief Wyjątek - nieznany błąd
//! @ingroup exceptions
class ElibriUnknownException extends Exception {

  function __construct() {
    parent::__construct("Unknow error", 1000);
  }
}

//! @brief Wyjątek - żaden serwer nie odpowiada
//! @ingroup exceptions
class ElibriNoServerResponsingException extends Exception {

  function __construct() {
    parent::__construct("No server responsing", 1001);
  }
}


//! @brief ElibriAPI abstrahuje wykorzystanie API udostępniane przez eLibri
class ElibriWatermarkingClient {

  private $token;
  private $secret;
  private $host;

  //! @brief Kontruktor obiektu API
  //! @param String $token - publiczny token eLibri Watermarking API
  //! @param String $secret - prywatny token eLibri Watermarking API
  //! @param Array $subdomains - lista subdomen, opcjonalnie. Przydatne, gdy używana wersja PHP nie zawiera metody dns_get_record
  function __construct($token, $secret, $host = "https://www.elibri.com.pl") {

    $this->token = $token;
    $this->secret = $secret;
    $this->host = $host;
  }

  //! @brief Zlecaj watermarkowanie.
  //! Żeby skrócić maksymalnie oczekiwanie klienta na plik, podzieliliśmy cały proces watermarkingu na dwa etapy.
  //! Proponujemy, żeby zlecać watermarking tak wcześnie, jak to tylko możliwe (na przykład wtedy, gdy klient opuści koszyk, i poda swoje dane)
  //! elibri rozpoczyna wtedy watermarkowanie książki, ale nie udostępnia jeszcze pliku sklepowi, ani nie rejestruje transakcji. Dopiero po dokonaniu
  //! płatności przez klienta należy wywołać metodę deliver, która to asynchronicznie dostarczy linki do plik do sklepu.

  //! @param String $ident - ISBN13 (bez myślików), lub record_reference
  //! @param String $formats - 'mobi', 'epub', lub 'mobi,epub'
  //! @param String $visible_watermark - stopka doklejana na końcu każdego rozdziału
  //! @param String $client_symbol - opcjonalny identyfikator promocji
  //! @return $transid - alfanumeryczny identyfikator transakcji
  function watermark($ident, $formats, $visible_watermark, $client_symbol = NULL) {
    if (preg_match('/^\d{13}$/', $ident)) {
      $ident_type = 'isbn';
    } else {
      $ident_type = 'record_reference';
    }

    if (!preg_match('/^(epub|mobi|pdf|mp3_in_zip|mp3_in_lpf|,)+$/', $formats)) {
      throw new ElibriWrongFormatsException();
    }

    $data = array($ident_type => $ident, 'formats' => $formats, 'visible_watermark' => $visible_watermark)

    if ($client_symbol) {
      $data['client_symbol'] = $client_symbol;
    }

    return $this->send_request('watermark', $data, TRUE);
  }

  //! @brief Dostarcz plik oraz zajestruj transakcję
  //! Ta metoda powinna zostać wywołana po watermark. Sklep powinien ją wywołać po zarejestrowaniu płatności przez klienta.
  //! Zwatermarkowany plik (pliki) zostaną przekopiowane do bucketu na amazon S3, który jest przypisany do sklepu.
  //! Sklep jest zobowiązany do wykasowania pliku po jego ściągnięciu.
  //! @param String $trans_id - alfanumeryczny identyfikator transakcji zwrócony przez metodę watermark
  function deliver($trans_id) {
    $data = array('trans_id' => $trans_id);
    return $this->send_request('deliver', $data, TRUE);
  }

  //! @brief Pobierz listę dostępnych plików
  //! Za pomocą tej metody możesz pobrać listę książek, które są lub będą w najbliższym czasie dostępne w systemie eLibri
  function available_files() {
    return json_decode($this->send_request('available_files.json', array(), FALSE), TRUE);
  }

  //! @brief Ponierz listę plików, których premiera się zbliża
  //! Za pomocą tej metody możesz pobrać listę ksiażek, które nie są w tej chwili dostępne, ale ich premiera  jest wkrótce
  function soon_available_files() {
    return json_decode($this->send_request('soon_available_files.json', array(), FALSE), TRUE);
  }

  //! @brief Ponierz listę plików, które wkrótce przestaną być dostępne
  //! Za pomocą tej metody możesz pobrać listę ksiażek, które wkrótce przestaną być dostępne
  function soon_unavailable_files() {
    return json_decode($this->send_request('soon_unavailable_files.json', array(), FALSE), TRUE);
  }

  private function send_request($method_name, $data, $do_post) {
    $stamp = time();
    $sig = rawurlencode(base64_encode(hash_hmac("sha1", $this->secret, $stamp, true)));
    $data['stamp'] = $stamp;
    $data['sig'] = $sig;
    $data['token'] = $this->token;

    $uri = $this->host . "/watermarking/$method_name";

    if (!$do_post) {
      $uri = $uri . "?" . http_build_query($data, '', '&');
    }

    $ch = curl_init($uri);

    //enable - to see debugging messages
    //curl_setopt($ch, CURLOPT_VERBOSE, TRUE);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    if ($do_post) {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
    }
    $curlResult = curl_exec($ch);
    try {
      return $this->validate_response($curlResult, $ch);
    } catch (ElibriServerErrorException $e) {
      //silency ignore this error
    } catch (ElibriUnknownException $e) {
      //silency ignore this error
    } catch (ElibriAPIConnectionException $e) {
      //silency ignore this error
    }
    throw new ElibriNoServerResponsingException();
  }

  private function validate_response($curlResult, $ch) {
    if ($curlResult === FALSE) {
      throw new ElibriAPIConnectionException(curl_error($ch), curl_errno($ch));
    }

    $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($response_code == 404) {
      throw new ElibriNotFoundException();
    } else if ($response_code == 408) {
      throw new ElibriRequestExpiredException();
    } else if ($response_code == 400) {
      throw new ElibriParametersError($curlResult);
    } else if ($response_code == 403) {
      throw new ElibriForbiddenException($curlResult);
    } else if ($response_code == 500) {
      throw new ElibriServerErrorException();
    } else if ($response_code == 401) {
      throw new ElibriInvalidAuthException();
    } else if (($response_code != 200) && ($response_code != 412) && ($response_code != 202)) {
      throw new ElibriUnknownException();
    }
    return $curlResult;
  }

}

?>
