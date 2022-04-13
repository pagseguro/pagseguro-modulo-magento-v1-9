var pagseguropayment = {
    grand_total: 0,
    processing: false,
    interestType: '',
    installments: 1,
    minInstallmentValue: 5,
    noInterestInstallments: 0,
    interestRate: 0,
    installmentsValue: [],

    invalidTaxvatNumbers: [
        '11111111111',
        '22222222222',
        '33333333333',
        '44444444444',
        '55555555555',
        '66666666666',
        '77777777777',
        '88888888888',
        '99999999999'
    ],

    identifyCcNumber: function (ccNumber) {
        ccNumber = ccNumber.replace(/\D/g, "");
        let creditCard = '';
        let visa = /^4[0-9]{12}(?:[0-9]{3})?$/;
        let master = /^((5[1-5][0-9]{14})$|^(2(2(?=([2-9][0-9]))|7(?=[0-2]0)|[3-6](?=[0-9])))[0-9]{14})$/;
        let amex = /^(34|37)\d{13}/;
        let elo = /^((((457393)|(431274)|(627780)|(636368)|(438935)|(504175)|(451416)|(636297))\d{0,10})|((5067)|(4576)|(4011))\d{0,12})$/;
        let hipercard = /^(606282\d{10}(\d{3})?)|^(3841\d{15})$/;
        let hiper = /^(((637095)|(637612)|(637599)|(637609)|(637568))\d{0,10})$/;

        if (elo.test(ccNumber)) {
            creditCard = 'EL';
        } else if (visa.test(ccNumber)) {
            creditCard = 'VI';
        } else if (master.test(ccNumber)) {
            creditCard = 'MC';
        } else if (amex.test(ccNumber)) {
            creditCard = 'AM';
        } else if (hiper.test(ccNumber)) {
            creditCard = 'HI';
        } else if (hipercard.test(ccNumber)) {
            creditCard = 'HC';
        }

        return creditCard;
    },
    validateCreditCard: function (s) {
        // remove non-numerics
        let v = "0123456789";
        let w = "";
        for (i = 0; i < s.length; i++) {
            x = s.charAt(i);
            if (v.indexOf(x, 0) != -1)
                w += x;
        }
        // validate number
        j = w.length / 2;
        k = Math.floor(j);
        m = Math.ceil(j) - k;
        c = 0;
        for (i = 0; i < k; i++) {
            a = w.charAt(i * 2 + m) * 2;
            c += a > 9 ? Math.floor(a / 10 + a % 10) : a;
        }
        for (i = 0; i < k + m; i++) c += w.charAt(i * 2 + 1 - m) * 1;
        return (c % 10 == 0);
    },
    removeCard: function (url, customerId, confirmMessage) {
        let self = this;
        if (confirm(confirmMessage)) {
            if (!self.processing) {
                let card = $j('select#savedCard option:selected').val();
                if (card != '0') {
                    self.processing = true;
                    $j.ajax({
                        url: url,
                        type: "post",
                        dataType: 'json',
                        data: {
                            'cId': card,
                            'custId': customerId
                        }
                    }).success(function (response) {
                        if (response.code == '200') {
                            $j('select#savedCard option:selected').remove();
                        }
                        self.processing = false;
                    }).error(function () {
                        self.processing = false;
                    });
                }
            }
        }
    },
    encryptCard: function (code, publicKey) {
        var card = PagSeguro.encryptCard({
            publicKey: publicKey,
            holder: $j('#' + code + '_owner').val(),
            number: $j('#' + code + '_number').val(),
            expMonth: $j('#' + code + '_expiration_month').val(),
            expYear: $j('#' + code + '_expiration_year').val(),
            securityCode: $j('#' + code + '_cid').val()
        });

        return card.encryptedCard;
    },
    updateTwoCardAmount: function (amount, field) {
        let secondaryCard = field === 'card_one' ? 'card_two' : 'card_one';

        let focusedCardAmountElement = $j('#pagseguropayment_twocc_' + field + '_amount');
        let secondCardAmountElement = $j('#pagseguropayment_twocc_' + secondaryCard + '_amount');

        let focusedCardAmount = parseNumber(focusedCardAmountElement.val().replace(/[^0-9.,]/g, '') || 0);

        if (focusedCardAmount < this.minInstallmentValue) {
            focusedCardAmount = this.minInstallmentValue;
            focusedCardAmountElement.val(this.minInstallmentValue);
        }

        let maxValue = this.grand_total - this.minInstallmentValue;

        if (focusedCardAmount > maxValue) {
            focusedCardAmount = maxValue;
            focusedCardAmountElement.val(maxValue);
        }

        let secondCardAmount = this.grand_total - focusedCardAmount;
        secondCardAmountElement.val(
            secondCardAmount.toFixed(2)
        );

        this.updateCardsInstallmentValues(focusedCardAmount, field);
        this.updateCardsInstallmentValues(secondCardAmount, secondaryCard);
    },
    updateCardsInstallmentValues: function (total, code) {
        let installments = this.installments;
        let interestRate = this.interestRate;
        let installmentsOptions = [];
        let value = 0;
        let text = '';

        installmentsOptions[1] = {
            'installments': 1,
            'value': total,
            'total': total,
            'interest_rate': 0,
        }

        for (let i = 2; i < installments; i++) {
            if ((installments > this.noInterestInstallments) && (i > this.noInterestInstallments)) {
                value = this.installmentValue(total, i);
                if (!value) {
                    continue;
                }
            } else {
                interestRate = 0;
                value = total;
            }

            if (value < this.minInstallmentValue && i > 1) {
                continue;
            }

            installmentsOptions[i] = {
                'installments': i,
                'value': value,
                'total': value * i,
                'interest_rate': interestRate,
            }
        }

        let selectElement = $j('#pagseguropayment_twocc_' + code + '_installments');
        selectElement.find('option').remove();
        $j.each(installmentsOptions, function (i, obj) {
            if (obj) {
                if (i < this.noInterestInstallments) {
                    text = "(sem juros)";
                } else {
                    text = " (Total de R$" + obj.total.toFixed(2) + ", juros de " + obj.interest_rate.toFixed(2) + "% a.m.)";
                }

                selectElement.append('<option value="' + obj.installments + '">' + obj.installments + 'x de R$' + obj.value + text + '</option>');
            }
        });
    },
    installmentValue: function (total, installments) {
        let interestRate = parseFloat(this.interestRate.toFixed(2)) / 100;
        let installmentValue = 0;

        if (installments > 0) {
            installmentValue = installments > 0 ? total / installments : total;
        }

        if (installments > this.noInterestInstallments && this.interestRate > 0) {
            switch (this.interestType) {
                case 'price':
                    installmentValue = total * (
                        (interestRate * Math.pow((1 + interestRate), installments)) /
                        (Math.pow((1 + interestRate), installments) - 1)
                    );
                    break;
                case 'compound':
                    installmentValue = (total * Math.pow(1 + interestRate, installments)) / installments;
                    break;
                case 'simple':
                    installmentValue = (total * (1 + (installments * interestRate))) / installments;
                    break;
            }
        }

        return parseNumber(installmentValue.toFixed(2));
    }
};
