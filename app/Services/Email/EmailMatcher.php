<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Core\Database;

/**
 * Work out which customer or lead an email belongs to.
 *
 * Two passes, and the second one is weaker on purpose:
 *
 *  1. EXACT ADDRESS. The address is on a customer or lead record. Certain.
 *  2. DOMAIN. Nobody at that exact address is on record, but somebody at the same company
 *     domain is - you emailed bob@cherokeecountyparks.org and the record has
 *     jane@cherokeecountyparks.org. This is where most of the real value is, because the
 *     person who enquired and the person who buys are frequently not the same person.
 *
 * Free-mail domains are excluded from pass 2 and that exclusion is the whole safety of it:
 * without it one gmail.com address would match every private customer in the table and
 * attach a stranger's email to them.
 *
 * Our own domains are excluded from matching altogether. Every BCC'd message has a USSC
 * address on it - that is what makes it ours - and matching on it would attach every email
 * to whichever customer record happens to carry a staff address.
 */
final class EmailMatcher
{
    /** Addresses at these domains are ours, never a counterparty. */
    private const OURS = ['usscproducts.com', 'usscos.com', 'stencilsplus.com'];

    /**
     * Shared mail providers. A match on these says only that two people both use Gmail.
     * Deliberately generous - a wrong entry here costs a manual attach; a missing one
     * attaches somebody's email to the wrong company.
     */
    private const SHARED = [
        'gmail.com', 'googlemail.com', 'yahoo.com', 'ymail.com', 'hotmail.com',
        'outlook.com', 'live.com', 'msn.com', 'aol.com', 'icloud.com', 'me.com',
        'mac.com', 'comcast.net', 'verizon.net', 'att.net', 'sbcglobal.net',
        'bellsouth.net', 'charter.net', 'cox.net', 'earthlink.net', 'protonmail.com',
        'proton.me', 'zoho.com', 'gmx.com', 'mail.com', 'yandex.com',
    ];

    /**
     * @param string[] $addresses Every address on the message - from, to and cc.
     * @return array{customer_id:?int, lead_id:?int, matched_on:?string, match_type:?string}
     */
    public function match(array $addresses): array
    {
        $candidates = $this->counterparties($addresses);
        $none = ['customer_id' => null, 'lead_id' => null, 'matched_on' => null, 'match_type' => null];

        if ($candidates === []) {
            return $none;
        }

        // Pass 1: the address itself.
        $placeholders = implode(',', array_fill(0, count($candidates), '?'));

        $row = Database::selectOne(
            "SELECT id, email FROM customers WHERE LOWER(email) IN ({$placeholders}) LIMIT 1",
            $candidates
        );
        if ($row) {
            return ['customer_id' => (int)$row['id'], 'lead_id' => null,
                    'matched_on' => $row['email'], 'match_type' => 'address'];
        }

        $row = Database::selectOne(
            "SELECT id, email, customer_id FROM leads WHERE LOWER(email) IN ({$placeholders}) LIMIT 1",
            $candidates
        );
        if ($row) {
            // A converted lead carries its customer, so the email reaches the account's
            // timeline as well as the lead's rather than being stranded behind it.
            return ['customer_id' => $row['customer_id'] ? (int)$row['customer_id'] : null,
                    'lead_id'     => (int)$row['id'],
                    'matched_on'  => $row['email'], 'match_type' => 'address'];
        }

        // Pass 2: the domain, where the domain belongs to a company rather than a provider.
        foreach ($this->companyDomains($candidates) as $domain) {
            $row = Database::selectOne(
                'SELECT id FROM customers WHERE LOWER(email) LIKE ? ORDER BY is_active DESC, id LIMIT 1',
                ['%@' . $domain]
            );
            if ($row) {
                return ['customer_id' => (int)$row['id'], 'lead_id' => null,
                        'matched_on' => $domain, 'match_type' => 'domain'];
            }

            $row = Database::selectOne(
                'SELECT id, customer_id FROM leads WHERE LOWER(email) LIKE ? ORDER BY id DESC LIMIT 1',
                ['%@' . $domain]
            );
            if ($row) {
                return ['customer_id' => $row['customer_id'] ? (int)$row['customer_id'] : null,
                        'lead_id'     => (int)$row['id'],
                        'matched_on'  => $domain, 'match_type' => 'domain'];
            }
        }

        return $none;
    }

    /** Lower-cased, de-duplicated, with our own addresses dropped. */
    private function counterparties(array $addresses): array
    {
        $out = [];
        foreach ($addresses as $address) {
            $address = strtolower(trim((string)$address));
            if ($address === '' || !str_contains($address, '@')) {
                continue;
            }
            if (in_array($this->domainOf($address), self::OURS, true)) {
                continue;
            }
            $out[$address] = true;
        }
        return array_keys($out);
    }

    /** @return string[] */
    private function companyDomains(array $addresses): array
    {
        $out = [];
        foreach ($addresses as $address) {
            $domain = $this->domainOf($address);
            if ($domain !== '' && !in_array($domain, self::SHARED, true)) {
                $out[$domain] = true;
            }
        }
        return array_keys($out);
    }

    private function domainOf(string $address): string
    {
        $at = strrpos($address, '@');
        return $at === false ? '' : strtolower(substr($address, $at + 1));
    }
}
