<?php

use Swapbot\Repositories\TransactionRepository;
use Tokenly\TokenGenerator\TokenGenerator;

class XChainNotificationHelper {

    function __construct() {
    }

    public function sampleSendNotificationForBot($bot, $override_vars=[]) {
        $override_vars = array_merge([
            'notifiedAddress'   => $bot['address'],
            'notifiedAddressId' => $bot['public_send_monitor_id'],
        ], $override_vars);
        return $this->sampleSendNotification($override_vars);
    }
    public function sampleSendNotification($override_vars=[]) {
        $override_vars = array_merge([
            "event"        => "send",
            "sources"      => ["1GHRfqhgC66E8dq2iDMwQsJVWegHV8s2ki"],
            "destinations" => ["1JztLWos5K7LsqW5E78EASgiVBaCe6f7cD"],
        ], $override_vars);

        return $this->sampleReceiveNotification($override_vars);
    }

    public function sampleReceiveNotificationForBot($bot, $override_vars=[]) {
        $override_vars = array_merge([
            'notifiedAddress'   => $bot['address'],
            'notifiedAddressId' => $bot['public_receive_monitor_id'],
        ], $override_vars);
        return $this->sampleReceiveNotification($override_vars);
    }
    public function sampleReceiveNotification($override_vars=[]) {
        $_j = '
        {
            "asset": "BITCRYSTALS",
            "bitcoinTx": {
                "blockhash": "000000000000000004bcf1f94625daa8985ea22c984f835ff19da43f7f037455",
                "blockheight": 378708,
                "blocktime": 1444746439,
                "fees": 0.0001,
                "locktime": 0,
                "size": 708,
                "txid": "28ea1471fbba842ed8d4a9ec48e328feab6abe30bbeaeea52bfdcac6419095a2",
                "valueIn": 0.00023826,
                "valueOut": 0.00013826,
                "version": 1,
                "vin": [
                    {
                        "addr": "1PBwMefEdUExqcVtLovg64vzvX7BU8TSDg",
                        "doubleSpentTxID": null,
                        "n": 0,
                        "scriptSig": {
                            "asm": "30450221009b3ad32c85e019103533d4cbffd863e51ad39c6094bc8512b6d6149b36071d260220323c79c9ef3415bc977dd8bd9a503f1c62ac94b33af70e9cc8eab95396ffadfc01 0249b29477f49713540302baf27a78f2db14d95f4b14682a857f7bef6d8aee73b9",
                            "hex": "4830450221009b3ad32c85e019103533d4cbffd863e51ad39c6094bc8512b6d6149b36071d260220323c79c9ef3415bc977dd8bd9a503f1c62ac94b33af70e9cc8eab95396ffadfc01210249b29477f49713540302baf27a78f2db14d95f4b14682a857f7bef6d8aee73b9"
                        },
                        "sequence": 4294967295,
                        "txid": "abd52b96651717c810d941ae682a373efb508bca9a88c0875065686d748f9496",
                        "value": 7.496e-05,
                        "valueSat": 7496,
                        "vout": 2
                    },
                    {
                        "addr": "1PBwMefEdUExqcVtLovg64vzvX7BU8TSDg",
                        "doubleSpentTxID": null,
                        "n": 1,
                        "scriptSig": {
                            "asm": "3045022100ad14b9877667f6ea1e1fd5582954365260885a4c6208a5ceb581563d90bc1e0402207350ad7d6a46c9b5f332dc4932fcd020b89f63de6c4699ed4197cf078e86518801 0249b29477f49713540302baf27a78f2db14d95f4b14682a857f7bef6d8aee73b9",
                            "hex": "483045022100ad14b9877667f6ea1e1fd5582954365260885a4c6208a5ceb581563d90bc1e0402207350ad7d6a46c9b5f332dc4932fcd020b89f63de6c4699ed4197cf078e86518801210249b29477f49713540302baf27a78f2db14d95f4b14682a857f7bef6d8aee73b9"
                        },
                        "sequence": 4294967295,
                        "txid": "7b13cd4b75274b871095f0efbf987a683b8bdaf93e0c6bd0291fa3b3c3e8299a",
                        "value": 5.47e-05,
                        "valueSat": 5470,
                        "vout": 0
                    },
                    {
                        "addr": "1PBwMefEdUExqcVtLovg64vzvX7BU8TSDg",
                        "doubleSpentTxID": null,
                        "n": 2,
                        "scriptSig": {
                            "asm": "3044022047ba41a514f53d7fc7a1b1479ff7838d1d940eca965669510b664a0124e74a3b02205da1f7f8767633ac4e0bf883f459906fb4041da102e397f369d67d0b78a4e45e01 0249b29477f49713540302baf27a78f2db14d95f4b14682a857f7bef6d8aee73b9",
                            "hex": "473044022047ba41a514f53d7fc7a1b1479ff7838d1d940eca965669510b664a0124e74a3b02205da1f7f8767633ac4e0bf883f459906fb4041da102e397f369d67d0b78a4e45e01210249b29477f49713540302baf27a78f2db14d95f4b14682a857f7bef6d8aee73b9"
                        },
                        "sequence": 4294967295,
                        "txid": "bc945c8c171ea871ddf46e562fc133b3e8457acf88a07416a434700ca104f27b",
                        "value": 5.43e-05,
                        "valueSat": 5430,
                        "vout": 0
                    },
                    {
                        "addr": "1PBwMefEdUExqcVtLovg64vzvX7BU8TSDg",
                        "doubleSpentTxID": null,
                        "n": 3,
                        "scriptSig": {
                            "asm": "3045022100e1eb2329f941f92e06689cac03ae872fa7867c09099e7db61d51cec03fcaa60c02200ad62301c0437a5d07444222a38fc5807f067bb0de8a17a90a9f583d204af28401 0249b29477f49713540302baf27a78f2db14d95f4b14682a857f7bef6d8aee73b9",
                            "hex": "483045022100e1eb2329f941f92e06689cac03ae872fa7867c09099e7db61d51cec03fcaa60c02200ad62301c0437a5d07444222a38fc5807f067bb0de8a17a90a9f583d204af28401210249b29477f49713540302baf27a78f2db14d95f4b14682a857f7bef6d8aee73b9"
                        },
                        "sequence": 4294967295,
                        "txid": "ff19603b953761c6116d7a7564007127dd2776b412cd5bc30a419523ad1454e9",
                        "value": 5.43e-05,
                        "valueSat": 5430,
                        "vout": 0
                    }
                ],
                "vout": [
                    {
                        "n": 0,
                        "scriptPubKey": {
                            "addresses": [
                                "1GHRfqhgC66E8dq2iDMwQsJVWegHV8s2ki"
                            ],
                            "asm": "OP_DUP OP_HASH160 a7a522a51998150ee19be3de15d8dacc11ee672c OP_EQUALVERIFY OP_CHECKSIG",
                            "hex": "76a914a7a522a51998150ee19be3de15d8dacc11ee672c88ac",
                            "reqSigs": 1,
                            "type": "pubkeyhash"
                        },
                        "value": "0.00005470"
                    },
                    {
                        "n": 1,
                        "scriptPubKey": {
                            "asm": "OP_RETURN 01b55e4a754de08b031b5d2a9387eaa1d825a0089c9faac701baaf71",
                            "hex": "6a1c01b55e4a754de08b031b5d2a9387eaa1d825a0089c9faac701baaf71",
                            "type": "nulldata"
                        },
                        "value": "0.00000000"
                    },
                    {
                        "n": 2,
                        "scriptPubKey": {
                            "addresses": [
                                "1PBwMefEdUExqcVtLovg64vzvX7BU8TSDg"
                            ],
                            "asm": "OP_DUP OP_HASH160 f3644d0d403b9fb238d8b0889b6ab499923f4ae6 OP_EQUALVERIFY OP_CHECKSIG",
                            "hex": "76a914f3644d0d403b9fb238d8b0889b6ab499923f4ae688ac",
                            "reqSigs": 1,
                            "type": "pubkeyhash"
                        },
                        "value": "0.00008356"
                    }
                ]
            },
            "blockSeq": 816,
            "confirmationTime": "2015-10-13T15:21:04+0000",
            "confirmations": 6,
            "confirmed": true,
            "counterpartyTx": {
                "asset": "BITCRYSTALS",
                "destinations": [
                    "1GHRfqhgC66E8dq2iDMwQsJVWegHV8s2ki"
                ],
                "dustSize": 5.47e-05,
                "dustSizeSat": 5470,
                "quantity": 480,
                "quantitySat": 48000000000,
                "sources": [
                    "1PBwMefEdUExqcVtLovg64vzvX7BU8TSDg"
                ],
                "type": "send",
                "validated": true
            },
            "destinations": [
                "1GHRfqhgC66E8dq2iDMwQsJVWegHV8s2ki"
            ],
            "event": "receive",
            "network": "counterparty",
            "notificationId": "106a03e7-3a1a-4226-9636-15a7ad592815",
            "notifiedAddress": "1GHRfqhgC66E8dq2iDMwQsJVWegHV8s2ki",
            "notifiedAddressId": "e06d0538-89b0-4222-a24c-bfe498b539f7",
            "quantity": 480,
            "quantitySat": 48000000000,
            "sources": [
                "1PBwMefEdUExqcVtLovg64vzvX7BU8TSDg"
            ],
            "transactionTime": "2015-10-13T12:54:50+0000",
            "txid": "28ea1471fbba842ed8d4a9ec48e328feab6abe30bbeaeea52bfdcac6419095a2",
            "transactionFingerprint": "ffff0000000000000000000000000000000000000000000000000000aaaaaaaa"
        }
';
        $out = json_decode($_j, true);

        $out = array_replace_recursive($out, $override_vars);

        return $out;
    }



    public function sampleInvalidationNotification($invalid_notification=null, $replacing_notification=null, $override_vars=[]) {
        $replacing_txid = ($replacing_notification !== null AND isset($replacing_notification['txid'])) ? $replacing_notification['txid'] : null;
        if ($replacing_txid === null) { $replacing_txid = '0000000000000000000000000000000000000000000000000000000088888888'; }

        $invalid_notification = ($invalid_notification === null) ? $this->sampleReceiveNotification($override_vars) : $invalid_notification;
        $replacing_notification = ($replacing_notification === null) ? $invalid_notification : $replacing_notification;
        $replacing_notification['txid'] = $replacing_txid;

        $notification = [
            'event'                 => 'invalidation',
            'notificationId'        => '11111111-2222-3333-4444-000000005555',
            'notifiedAddress'       => $invalid_notification['notifiedAddress'],
            'notifiedAddressId'     => $invalid_notification['notifiedAddressId'],
            'invalidTxid'           => $invalid_notification['txid'],
            'replacingTxid'         => $replacing_txid,
            'invalidNotification'   => $invalid_notification,
            'replacingNotification' => $replacing_notification,
        ];

        return $notification;

    }

}
