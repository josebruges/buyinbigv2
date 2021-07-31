<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once("vendor/autoload.php"); 

/* Start to develop here. Best regards https://php-download.com/ */

use \Firebase\JWT\JWT;

$privateKey = <<<EOD
-----BEGIN RSA PRIVATE KEY-----
MIIJKQIBAAKCAgEA437i1tjDAI8kEg842dwDvRibmqKtYBjkE18HLRJacr1hBf8+
hyWxyTWyGMAnfKvrXREfgmZGS0HlCX/Qdg2UjIdybr5Bs8S/eAlLq6EozD0W5L47
f0joGf41E0aScLMvCGhUPbmfXgLtGtroZtzrZGpp4Ep9pFuPbGuIohgXMEa3qXrI
EhTB4vUHmbC8LCjjyKLJIH7XJVKvnZSITvrdy91BIKFnH8H6PdjnfnBvgoRCIJx5
AigrRCv1HkhNpx2g/4rbybU26aVfodxV+1sWbJ8vzhVv0RpSPq5nAQdAad2BATo1
n6XK5JPd9QevlyQ/pfXooPA0XFfylSv+8Tl+itRKD3zUC2jkaN7jLkdOd3OaQtzF
R5x7vjnOW9qK0hbubSicGM+ukW8UOc8y/BDqrwA9vm1IvdDH8zNbAa6k4Qns7GPF
ht2uuRSC8N/D6guoLBiVAWM/zFBBxIbxucqyVY3HW0vdBeY8EN16FarAvCahdwaj
Ai8uR/dNsdpNBH07rmaSlEVxJgwG6kR+henUsZfSUBKWMTXW9aSmtoVLqE1ur1t1
FvNxlGmtt+icevYIj6vPY5vfUalYbQFggE+834U0dK3g1uccWc182C90t9l4M7jT
EzkDtPKBIqOJrqvTDgio6AiGOgovgk3iFb6vxQ0LQpRei4/kQP1Jsq5diRcCAwEA
AQKCAgBQ9OSQhE7Qsh+p9ZTLoooKDunA28dK/VCcvCJ3naJmVpJiafS8b2OXMO6R
9D+ZFC0Lz34hD3hQa8Cv4rYybJ7Ca6kDU91ZtdhVRSmSiCVWmR6+hBv0LeO67EXC
+EhApuND4Kyp0pauA+iQt/ogMNnrwBqxYJDZnmOpt5LF7EDEQ0Y0n6b3GD/vHjd4
L/am2F5HFfbxA3JYq9YnA6aGRKwNUk1M6WMjYApHXBr1Wdm03pARvt0pMVZESkhO
YHiR0e9rQOT6IOzLtLgVh61pZ37RZMzET8ic5vsBp1qseQIpKlQwNaDDkWJkeWJx
QizwYkvsKViBfTmE6x8+iouNGGK5JQ5T54fcyYU5RdF+UPZAIswc1Z5HTaysCqBB
MykUN4iZ8uB7TDzpLSUX1QfKQQKTNhRuCLzqUnpSPvLnDR2fNH8ev//JOmNCFebz
uJY+sneGGK4DNQTckH7v+1ISmaGq7kEpql/+ezrSDOjNSJC3cse0eEDKw/dScAN/
HJ8i+DYq5/Tom8eCy5lLgzrHuKQjQxkrvQtsUrK5inSro+W49XAfk65TFp+Y28HR
Mt8ucpIp3llbG86P4BrOmmDsZG1BPrcea/7Yddc6PfbPuGLE/DXyMIX9rMOHkrWN
9MSwFLYoK8myO7OBUATgrFGwZAD9o2KFz2A5BKeSQutwRnzloQKCAQEA9UkMIP/S
3T8mQmUsw0qtrJdJTlUiPNUYl7fgCydq4KAvCS4ScYz/oQwOrRWIvm6gkLpe6umE
F6YQRbckeADS1tN8WtvDU84oWqocCn860C3wK8uURWX8VN6WXFssNd6OFK8RWat0
Fwu3By0/5LVbOuuNG92dhHJYFCfVNp1BtNq0gBJtssBwHgpLQ/8dUVC4Zk35iWTp
kHtVCTfAOr6g/+FAv1fqf2/XLxrCbxhAYhCNedjWusdtubF5DLxiWnz1Xa36oCec
IcUYvXBRMWWCvrtdHl1AkyZcngZJqmarzAJ0N7UbhpbWoXommYEW0O6RPkRDnsF4
7XQ/33srLgDvTwKCAQEA7W7m4vSZTSpr6pgsTE//rwkgODFC0FayBcwmowoD6N3Y
qMnJ5lm2DKmPXyZktt5zG3UDpJZLk/rd+YxaFbmh25EuLxWdDVn8LPtYsORFwoLb
CrVRbAEt/ndk8AP4Y3NrRONs943UsDK61KqNcZbZYp7C0X0cbniDbBg9A5lI8qYY
dVUMueXgMy7tJpV2IB+8FU1Kl0CAJyhXYRQjqbRWf53Xl/zfH1iz59iE47OXDIfj
GnWZ7ck8AZ/l6hCPF3UGGf942xtVuOWItLXyM0T04S/vq0vu8A3nDBY0j0qxoYIV
2q3gbQ9oSbU9qLe0VYOJ8v+18XEpUygpkkYCWqeXuQKCAQB6jgHyZwALrabHG9gu
x5xZFMn6yoytymdcPvJBgHNlQoPd2Vg7xhBffi/DCXRmec3eIlCUnRJz4nRU8PnW
v8qYrdh/aTiCIgqFqmMSV5miNKHAsBooRpm6KfEEukUvsUBaU1Ke9AywCxlrC4/W
DUtE+DZsCuHOMHeTsodUn2QaIgB30T1gR8h9KfWLRud2HpfT/ffFT1dn6keFsIZq
k57L2tdIA3xWKiJhFcS6T2qz1MNrcw1vC3/mfScXVvTpc3ABW5FS/heDw//lfz4C
KU8XT3RqXHuMSmmSR1/9NQl3iG5a+zGCoNEio26v2hD1WY3Wh7MlmlN6iU/0sIzV
kn//AoIBAQDSt9EbbP8898AsxH7T/0+o1U2N8VIdukNmV8eWfMX5fiHRsryHveiO
llFSna1WilfX72KbSHOs0kD8Py6YB1Z5mgBF80Wd2tWuSidtXjn3JdEmUMMBOo7K
PsYLKUrhYEa8LabAkVRIR819/htnyvwExWebKhD4jeX9IgnMTtbp2rTsNN4FgdkX
M7duvcjHO8LdmOnxEGJr3iamVoxMCWuW+Muk7NxMYpnP0l4mA3Wzvkm0atiAq06h
Fnqi361OoSIYIp3svva2EKfh0XQxQiqeS7/F468bxwrdtDtTTHQXJW8l05jQ0ZK5
j6s4OElId2QPkBe2PrrxPTyfv5hJQmGpAoIBAQCYrM2MxaSryJLeJeecsFXpedne
TwNYGONbRXm1bEJ2GYmWe9yXV3rKdP9NJg3iQLn6UqxZvkhVBnHSVgUYFZMUdyqw
3Tps0U8sAa5ztzhMnhrzLXcZTxN5R1fDO1XgolcJTLFi6i8Aa8vKWT4JPqYfJW9C
N2E9DsQvs0u36S6rzos0+6bSL8PVAo0GhCcKOK5w1jGwITNtlAUrwV8PrLjr8weI
p+l8wbnpRWvaPKAc55nzp7LX9S0NKLSfK5pAqW7+WsYvp4xuNEXjzwXNYHte7hLg
XL8jDUmvRLF2zynlJo630XzIUPnp7JIlwUgUk1LzKSNVHHMlHtmSDALofzB+
-----END RSA PRIVATE KEY-----
EOD;

$publicKey = <<<EOD
-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA437i1tjDAI8kEg842dwD
vRibmqKtYBjkE18HLRJacr1hBf8+hyWxyTWyGMAnfKvrXREfgmZGS0HlCX/Qdg2U
jIdybr5Bs8S/eAlLq6EozD0W5L47f0joGf41E0aScLMvCGhUPbmfXgLtGtroZtzr
ZGpp4Ep9pFuPbGuIohgXMEa3qXrIEhTB4vUHmbC8LCjjyKLJIH7XJVKvnZSITvrd
y91BIKFnH8H6PdjnfnBvgoRCIJx5AigrRCv1HkhNpx2g/4rbybU26aVfodxV+1sW
bJ8vzhVv0RpSPq5nAQdAad2BATo1n6XK5JPd9QevlyQ/pfXooPA0XFfylSv+8Tl+
itRKD3zUC2jkaN7jLkdOd3OaQtzFR5x7vjnOW9qK0hbubSicGM+ukW8UOc8y/BDq
rwA9vm1IvdDH8zNbAa6k4Qns7GPFht2uuRSC8N/D6guoLBiVAWM/zFBBxIbxucqy
VY3HW0vdBeY8EN16FarAvCahdwajAi8uR/dNsdpNBH07rmaSlEVxJgwG6kR+henU
sZfSUBKWMTXW9aSmtoVLqE1ur1t1FvNxlGmtt+icevYIj6vPY5vfUalYbQFggE+8
34U0dK3g1uccWc182C90t9l4M7jTEzkDtPKBIqOJrqvTDgio6AiGOgovgk3iFb6v
xQ0LQpRei4/kQP1Jsq5diRcCAwEAAQ==
-----END PUBLIC KEY-----
EOD;

$payload = array(
    "id" => "Probandotokens",
    "ingreso" => (new DateTime())->getTimestamp()
);

$jwt = JWT::encode($payload, $privateKey, 'RS512');
echo "Encode:<br/>" . print_r($jwt, true) . "<br/>";

$decoded = JWT::decode($jwt, $publicKey, array('RS512'));

/*
 NOTE: This will now be an object instead of an associative array. To get
 an associative array, you will need to cast it as such:
*/

$decoded_array = (array) $decoded;
echo "Decode:<br/>" . print_r($decoded_array, true) . "<br/>";
