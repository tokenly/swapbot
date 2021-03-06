{
    "event": "receive",

@if ($asset == 'BTC')
    "network": "bitcoin",
@else
    "network": "counterparty",
@endif
    "asset": "{{ $asset }}",
    "quantity": {{ $quantity }},
    "quantitySat": {{ round($quantity * 100000000) }},

    "sources": ["{{ $sender }}"],
    "destinations": ["{{ $bot['address'] }}"],

    "notificationId": "{{ $notificationId }}",
    "txid": "{{ $txid }}",
    "transactionTime": "{{ date('c', $timestamp) }}",
    "confirmed": {{ $confirmations > 0 ? 'true' : 'false' }},
    "confirmations": {{ $confirmations }},
    "blockSeq": 359,

    "notifiedAddress": "{{ $bot['address'] }}",
    "notifiedAddressId": "{{ $bot['public_receive_monitor_id'] }}",

@if ($asset != 'BTC')
"counterpartyTx": {
    "type": "send",
    "sources": ["{{ $sender }}"],
    "destinations": ["{{ $bot['address'] }}"],
    "quantity": {{ $quantity }},
    "quantitySat": {{ round($quantity * 100000000) }},
    "dustSize": 5.43e-5,
    "dustSizeSat": 5430,
    "asset": "{{ $asset }}"
},
@endif

    "bitcoinTx": {
        "txid": "d800ef4c33542c90bcfe4cd0c2fc2c0d120877ec933ca869177a77eb8b42077e",
        "version": 1,
        "locktime": 0,
        "vin": [
            {
                "txid": "e82d3d73bf53107f0b7713831f775e8489bd28f6cb6a24cd2a5edf9426ccdce4",
                "vout": 0,
                "scriptSig": {
                    "asm": "304502210085ec7c4cf1f13ce99a5e55a12bc8e989a1cf145a416b6c176e498e2a1f9984e902202674b68cfdd8d2612f985396a7b5d4aeec316b7f26e1210cde8e8daf5497bd6401 0257b0d96d1fe64fbb95b2087e68592ee016c50f102d8dcf776ed166473f27c690",
                    "hex": "48304502210085ec7c4cf1f13ce99a5e55a12bc8e989a1cf145a416b6c176e498e2a1f9984e902202674b68cfdd8d2612f985396a7b5d4aeec316b7f26e1210cde8e8daf5497bd6401210257b0d96d1fe64fbb95b2087e68592ee016c50f102d8dcf776ed166473f27c690"
                },
                "sequence": 4294967295,
                "n": 0,
                "addr": "1291Z6hofAAvH222222N9M5uKB1VvwBnup",
                "valueSat": 5430,
                "value": 5.43e-5,
                "doubleSpentTxID": null
            },
            {
                "txid": "3fcf1f4b90b22375c0f692153f21851984b2aa9ea798483a191c74662699578b",
                "vout": 1,
                "scriptSig": {
                    "asm": "3045022100b6a44975c2d15dece8243908f99ec29f4b27fe9e8bb5b36add7814be24f76a3302200c282fff6a7cb043ff8b4104fc9310ee8d0b441e000d4517ac2f065778f1ecc201 0257b0d96d1fe64fbb95b2087e68592ee016c50f102d8dcf776ed166473f27c690",
                    "hex": "483045022100b6a44975c2d15dece8243908f99ec29f4b27fe9e8bb5b36add7814be24f76a3302200c282fff6a7cb043ff8b4104fc9310ee8d0b441e000d4517ac2f065778f1ecc201210257b0d96d1fe64fbb95b2087e68592ee016c50f102d8dcf776ed166473f27c690"
                },
                "sequence": 4294967295,
                "n": 1,
                "addr": "1291Z6hofAAvH222222N9M5uKB1VvwBnup",
                "valueSat": 401890,
                "value": 0.0040189,
                "doubleSpentTxID": null
            },
            {
                "txid": "fe000c79f326d503258dbf70b0d5c06688f5d8c3092351877042ad7b317e1949",
                "vout": 0,
                "scriptSig": {
                    "asm": "3045022100c7654ab044e9bbad65b916c93701971b1a63577da47a55e93e4bec82e9f3cb570220682763b4852d8cc0180cca39942957842245180b864d0af409ff51e288c54a5601 0257b0d96d1fe64fbb95b2087e68592ee016c50f102d8dcf776ed166473f27c690",
                    "hex": "483045022100c7654ab044e9bbad65b916c93701971b1a63577da47a55e93e4bec82e9f3cb570220682763b4852d8cc0180cca39942957842245180b864d0af409ff51e288c54a5601210257b0d96d1fe64fbb95b2087e68592ee016c50f102d8dcf776ed166473f27c690"
                },
                "sequence": 4294967295,
                "n": 2,
                "addr": "1291Z6hofAAvH222222N9M5uKB1VvwBnup",
                "valueSat": 500000,
                "value": 0.005,
                "doubleSpentTxID": null
            }
        ],
        "vout": [
            {
                "value": "0.00101000",
                "n": 0,
                "scriptPubKey": {
                    "asm": "OP_DUP OP_HASH160 9c2401388e6d2752a496261e9130cd54ddb2b262 OP_EQUALVERIFY OP_CHECKSIG",
                    "hex": "76a9149c2401388e6d2752a496261e9130cd54ddb2b26288ac",
                    "reqSigs": 1,
                    "type": "pubkeyhash",
                    "addresses": [
                        "{{ $bot['address'] }}"
                    ]
                }
            },
            {
                "value": "0.00801320",
                "n": 1,
                "scriptPubKey": {
                    "asm": "OP_DUP OP_HASH160 0c7bea5ae61ccbc157156ffc9466a54b07bfe951 OP_EQUALVERIFY OP_CHECKSIG",
                    "hex": "76a9140c7bea5ae61ccbc157156ffc9466a54b07bfe95188ac",
                    "reqSigs": 1,
                    "type": "pubkeyhash",
                    "addresses": [
                        "1291Z6hofAAvH222222N9M5uKB1VvwBnup"
                    ]
                }
            }
        ],
        "valueOut": 0.0090232,
        "size": 522,
        "valueIn": 0.0090732,
        "fees": 5.0e-5,
        "blockhash": "0000000000000000148c1ae2b62db079f8cc21501141e4bd582bebd1aa9b1c7f",
        "blocktime": 1421379546,
        "blockheight": 339155
    },

    "transactionFingerprint": "0000000000000000000000000000000000000000000000000000000000111111"
}
