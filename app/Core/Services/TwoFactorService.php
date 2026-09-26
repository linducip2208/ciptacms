<?php
namespace App\Core\Services;
// TOTP RFC6238 native (no dependency). Base32 secret, 30s step, 6 digits.
class TwoFactorService {
    public function generateSecret(int $len=20): string {
        $bytes = random_bytes($len);
        return $this->base32Encode($bytes);
    }
    public function otpauthUrl(string $label, string $secret, string $issuer='Lindu CMS'): string {
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$label).'?secret='.$secret.'&issuer='.rawurlencode($issuer).'&digits=6&period=30';
    }
    public function verify(string $secret, string $code, int $window=1): bool {
        $code = preg_replace('/\D/','',$code);
        if(strlen($code)!==6) return false;
        $t = (int)floor(time()/30);
        for($i=-$window;$i<=$window;$i++){ if(hash_equals($this->hotp($secret,$t+$i),$code)) return true; }
        return false;
    }
    public function backupCodes(int $n=8): array {
        $out=[]; for($i=0;$i<$n;$i++) $out[]=strtoupper(substr(bin2hex(random_bytes(5)),0,10));
        return $out;
    }
    protected function hotp(string $secret, int $counter): string {
        $key = $this->base32Decode($secret);
        $bin = pack('N*',0,$counter);
        $hash = hash_hmac('sha1',$bin,$key,true);
        $offset = ord($hash[19]) & 0x0f;
        $code = ((ord($hash[$offset])&0x7f)<<24)|((ord($hash[$offset+1])&0xff)<<16)|((ord($hash[$offset+2])&0xff)<<8)|(ord($hash[$offset+3])&0xff);
        return str_pad((string)($code%1000000),6,'0',STR_PAD_LEFT);
    }
    protected function base32Encode(string $data): string {
        $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $out=''; $bits=0; $val=0;
        foreach(str_split($data) as $c){ $val=($val<<8)|ord($c); $bits+=8; while($bits>=5){ $out.=$alphabet[($val>>($bits-5))&31]; $bits-=5; } }
        if($bits>0) $out.=$alphabet[($val<<(5-$bits))&31];
        return $out;
    }
    protected function base32Decode(string $b32): string {
        $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $b32=strtoupper(preg_replace('/[^A-Z2-7]/','',$b32));
        $out=''; $bits=0; $val=0;
        foreach(str_split($b32) as $c){ $val=($val<<5)|strpos($alphabet,$c); $bits+=5; if($bits>=8){ $out.=chr(($val>>($bits-8))&255); $bits-=8; } }
        return $out;
    }
}
