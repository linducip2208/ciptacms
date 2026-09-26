<?php

namespace App\Core\Contracts;

interface LicenseProvider
{
    /** The license key currently installed on this machine, if any. */
    public function currentKey(): ?string;

    /**
     * Validate a key against the provider.
     *
     * @return array{ok:bool,message:string,features?:array,expires_at?:?string,entitled_version?:?string}
     */
    public function verify(string $key, string $domain): array;

    /** Release a previously used activation slot. */
    public function deactivate(string $key, string $domain): bool;
}
