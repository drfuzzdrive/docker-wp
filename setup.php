<?php
$domains_dir = "./domains";


$domains = array_diff(scandir($domains_dir), array('..', '.'));
foreach ($domains as $domain) {

    # Если к названию папки добавить суффикс _old
    # для домена не будет сформирован конфиг nginx 
    # Например, для домена site.com_old
    # Конфиг сформирован не будет
    if(strcasecmp(substr($domain, -4), "_old") != 0) {

        # Проверка конфига домена nginx
        # Проверяется только название файла.
        # Если конфиг уже существует значит сайт работает как надо.
        if (!check_nginx_config($domain)) {
        
            # Проверка А записи домена. Если А запись не совпадает с IP сервера
            # Будет выведено предупреждение
            if(check_dns($domain)) {

                # Проверяем наличие файла wp-config.php
                # Если файла не существует, база данных и пользователь
                # Не будут созданы автоматически
                if (check_wp_config($domain)) {
                    # Получаем параметры доступа из wp-config.php
                    $credentials = get_wp_credentials($domain);
                    # Создаем базу данных и пользователя, если база не существует
                    create_mysql_credentials($credentials, $domain);
                    #Создаем nginx конфиг для домена
                    create_nginx_config($domain);
                }
                
            }
        }
    }
}



function check_nginx_config($domain) {
    # Проверяем существует ли файл nginx с именем домена
    if (file_exists("./nginx/$domain.conf")) {
        return true;
    }
    else return false;
}

function create_nginx_config($domain) {
    $nginx_default_pass = "./nginx/";
    $default_nginx_config_file = "./nginx/default";
    # Копируем дефолтный конфиг nginx с именем домена
    # И заменяем домен default на требуемый домен
    copy ("$default_nginx_config_file", "$nginx_default_pass$domain.conf");
    $file_contents = file_get_contents("$nginx_default_pass$domain.conf");
    $file_contents = str_replace('default', $domain, $file_contents);
    file_put_contents("$nginx_default_pass$domain.conf", $file_contents);
}

function check_dns($domain) {
    # В массиве харнятся IP адреса сервера
    # Функция array_search выдает значение 0, если совпадает первый IP адрес.
    # Чтобы пройти валидацию, первым элементом массива должно быть абстрактное значение
    $server_ip_addresses = array('555', '185.102.185.160');
    $dns_records = dns_get_record($domain, $type=DNS_A);
    if(count($dns_records) > 0) {
        if (array_search($dns_records[0]["ip"], $server_ip_addresses) == true) {
            //echo $dns_records[0]["ip"];
            return true;
        } 
        else {
            echo "Внимание!!! А запись домена не ссылается на этот сервер \nВы желаете продолжить установку сайта $domain? \n";
            return user_answer();
        }
    } 
    else {
        echo "Внимание!!! Для домена не прописана А запись!\nВы желаете продолжить установку сайта $domain? \n";
        return user_answer();
    }
}

function user_answer() {
    $a = readline("Введите yes для продолжения установки \n");
    if($a == 'yes') {
        return true;
    }
    else return exit;
}

function check_wp_config($domain) {
    if (file_exists("./domains/$domain/wp-config.php")) {
        return true;
    }
    else {
        echo "Для домена $domain не найден файл wp-config.php \n База данных создана не будет \n";
        user_answer();
    }
}

function get_wp_credentials($domain) {
    $file_contents = file_get_contents("./domains/$domain/wp-config.php");

    $credentials = array();
    preg_match('/\'DB_NAME\'.+\'(.*)\'/', $file_contents, $db_name);
    $credentials['db'] = $db_name[1];
    preg_match('/\'DB_USER\'.+\'(.*)\'/', $file_contents, $db_user);
    $credentials['user'] = $db_user[1];
    preg_match('/\'DB_PASSWORD\'.+\'(.*)\'/', $file_contents, $db_pass);
    $credentials['pass'] = $db_pass[1];
    return $credentials;
}

function create_mysql_credentials($credentials, $domain){

    $mysqli = new mysqli("localhost", "root", "Ckj;ysqGfhjkm!");
    if ($mysqli->connect_errno) {
        echo "Не удалось подключиться к MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    }

    $result = $mysqli->query('show databases');
    $show_databases = mysqli_fetch_all($result);
    foreach($show_databases as $database) {
        $db[] = $database[0];
    }
        
    if(array_search($credentials['db'], $db) == true) {
        
        $db_exists = "Домен $domain. База данных ". $credentials['db'] ." существует.";
        //return $db_exists;
    }
    else {
        $sql_query_string = "create database ". $credentials['db'];
        if($mysqli->query("$sql_query_string") == true) {
            $cred_create['db'] = "База данных ". $credentials['db'] ." создана";
        }
    }
    $result = $mysqli->query('select user from mysql.user');
    $show_users = mysqli_fetch_all($result);
    foreach($show_users as $users) {
        $user[] = $users[0];
    }

    if(array_search($credentials['user'], $user) == true) {
        //$user_exists = "Домен: $domain. Пользователь ". $credentials['user'] ." существует.";
    }
     else {
         $sql_query_string = "create user ". $credentials['user'] ."@localhost identified by '". $credentials['pass'] ."'";
        if($mysqli->query("$sql_query_string") == true) {
           $cred_create['user'] = "Пользователь ". $credentials['user'] ." создан";
       }
    }
    $sql_query_string = "show grants for ". $credentials['db'] ."@localhost";
    $result = $mysqli->query($sql_query_string);
    $show_grants = mysqli_fetch_all($result);
    if($show_grants[1][0] == "GRANT ALL PRIVILEGES ON `". $credentials['db'] ."`.* TO `". $credentials['user'] ."`@`localhost`") {
    }
    else {
        $sql_query_string = "grant all on '". $credentials['db'] ."'.* to '". $credentials['user'] ."'@'localhost identified by '". $credentials['pass'] ."'";
        if($mysqli->query("$sql_query_string") == true) {
            $cred_create['grants'] = "Пользователю ". $credentials['user'] ." назначена база данных ". $credentials['db'];
        }   
    }
}