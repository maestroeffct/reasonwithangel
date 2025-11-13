/**
 *#################################################################################################
 *########################################### MANAGE PAYMENT #######################################
 *##################################################################################################
 */
//initialize variables
const loader                    = document.getElementById('loadermodal');
const paymentbtn                = document.getElementById('paymentbtn');
const step1payment              = document.getElementById('step1payment');
const step2payment              = document.getElementById('step2payment');
const step3payment              = document.getElementById('step3payment');
const containerrecap            = document.getElementById('transactionfeeinfo');
const authapiurl                = document.getElementById('authapiurl').value;
const commissionurl             = document.getElementById('commissionurl').value;
const cashouturl                = document.getElementById('cashouturl').value;
const getquoteurl               = document.getElementById('getquoteurl').value;
const collectionurl             = document.getElementById('collectionurl').value;
const collectionstatusurl       = document.getElementById('collectionstatusurl').value;
const visatokenurl              = document.getElementById('visatokenurl').value;
const visapaymentlinkurl        = document.getElementById('visapaymentlinkurl').value;
const visacollectionstatusurl   = document.getElementById('visacollectionstatusurl').value;
const errormessage          = document.getElementById('error_message');
var btntransaction          = '';
var checkoutorderid         = '';
var checkoutamount          = '';
var checkouttotalamount     = '';
var transactionfee          = '';
var api_auth_token          = '';
var visa_access_token       = '';
var currency                = 'XAF';
var transactionref          = '';
var visapaymentlink         = '';
var timerbeforecheckstatus  = 5000; //check every 5 seconds
var timercheckstatus        = 10000; //check every 10 seconds
var servicecode             = '';

const togglePaymentChannel = (data) => {
    var classname   = data.getAttribute('data-class');
    var image       = data.getAttribute('image');
    checkoutamount  = data.getAttribute('totalprice');
    checkoutorderid = data.getAttribute('orderid');
    currency        = data.getAttribute('currency');
    if(classname === "AFPManager"){
        $('#afpmanager_logo').attr('src', image);
        $('#openPayment').modal('show');
    }
}

// Calculate payment commission
const getCommissionPrice = (data) => {
    servicecode                 = data.value;
    checkouttoken               = $("input[name=_token]").val();
    const auth_request_data = {
        _token     : checkouttoken,
    }
    var btnVal = $('#paymentbtn').text();
    if(servicecode !== ''){
        $.ajax({
            type        : 'post',
            url         : authapiurl,
            data        : auth_request_data,
            datatype    : 'json',
            beforeSend: function () {
                $(document.body).css({'cursor' : 'wait'});
                loader.style.display = 'block';
                errormessage.style.display = 'none';
                $('#paymentbtn').text('Calculation of fees in progress...').prop('disabled', true);
            },
            success: function (json_auth) {
                if (json_auth.status == 200){
                    const resulttoken = json_auth.token;
                    const commissionrequest = {
                        api_token           : resulttoken,
                        serviceid           : servicecode,
                        amount              : checkoutamount,
                        _token              : checkouttoken,
                    }
                    $.ajax({
                        type        : 'post',
                        url         : commissionurl,
                        data        : commissionrequest,
                        datatype    : 'json',
                        beforeSend: function () {
                            $(document.body).css({'cursor' : 'wait'});
                            errormessage.style.display = 'none';
                        },
                        success: function (json) {
                            if (json.status == 200){
                                const resultcominfos = json.result;
                                $('#initialprice').text(checkoutamount+' FCFA');
                                $('#commissionamount').text(resultcominfos.commission+' FCFA');
                                $('#finalamount').text(resultcominfos.finalamount+' FCFA');
                                checkouttotalamount             = resultcominfos.finalamount;
                                containerrecap.style.display    = 'block';
                                loader.style.display            = 'none';
                                $('#paymentbtn').text(btnVal).prop('disabled', false);
                            }else{
                                window.scrollTo({'top':0, 'behavior':'smooth'});
                                $(document.body).css({'cursor' : 'default'});
                                loader.style.display = 'none';
                                errormessage.style.display = 'block';
                                $('#error_message').text(json.message).css('color', 'red');
                                $('#paymentbtn').text(btnVal).prop('disabled', false);
                                document.getElementById('paymentMethodOption').selectedIndex = 0;
                            }
                        },
                        complete: function () {
                            $(document.body).css({'cursor' : 'default'});
                        },
                        error: function(jqXHR, textStatus, errorThrown){}
                    });
                }else{
                    window.scrollTo({'top':0, 'behavior':'smooth'});
                    $(document.body).css({'cursor' : 'wait'});
                    loader.style.display = 'none';
                    errormessage.style.display = 'block';
                    $('#error_message').text(json_auth.message).css('color', 'red');
                    $('#paymentbtn').text(btnVal).prop('disabled', false);
                    document.getElementById('paymentMethodOption').selectedIndex = 0;
                }
            },
            complete: function () {
                $(document.body).css({'cursor' : 'default'});
                $('#paymentbtn').text(btnVal).prop('disabled', true);
            },
            error: function(jqXHR, textStatus, errorThrown){}
        });
    }
}

/**Init payment Form */
$(document).on('submit', '#requestPayment', function (e) {
    e.preventDefault();
    var mobilewallet        = $("input[name=mobilewallet]").val();
    var customer_name       = $("input[name=customer_name]").val();
    var customer_address    = $("input[name=customer_address]").val();
    var form                = $(this);
    const auth_request_data = {
        _token     : checkouttoken,
    }
    btntransaction      = $('#paymentbtn').text();
    $.ajax({
        type: 'post',
        url:  authapiurl,
        data: auth_request_data,
        datatype: 'json',
        beforeSend: function () {
            loader.style.display = 'block';
            $(document.body).css({'cursor' : 'wait'});
            $('#paymentbtn').text('en cours...').prop('disabled',true);
            errormessage.style.display = 'none';
            form.find('*').prop('disabled', true);
        },
        success: function (json_auth) {
            if (json_auth.status == 200){
                if(json_auth.token !== ''){
                    api_auth_token = json_auth.token;
                    if(servicecode === '30056' || servicecode === '20056'){
                        const cashout_request_data = {
                            api_token               : api_auth_token,
                            serviceid               : servicecode,
                            _token                  : checkouttoken,
                        }
                        $.ajax({
                            type: 'post',
                            url:  cashouturl,
                            data: cashout_request_data,
                            datatype: 'json',
                            beforeSend: function () {
                                $(document.body).css({'cursor' : 'wait'});
                                $('#paymentbtn').text('Initialisation...').prop('disabled',true);
                                errormessage.style.display = 'none';
                            },
                            success: function (json_cashout) {
                                if (json_cashout.status == 200){
                                    const cashout_result = json_cashout.result;
                                    const get_quote_request_data = {
                                        api_token               : api_auth_token,
                                        payItemId               : cashout_result.payItemId,
                                        serviceid               : cashout_result.serviceid,
                                        amount                  : checkoutamount,
                                        _token                  : checkouttoken,
                                    }
                                    $.ajax({
                                        type: 'post',
                                        url:  getquoteurl,
                                        data: get_quote_request_data,
                                        datatype: 'json',
                                        beforeSend: function () {
                                            $(document.body).css({'cursor' : 'wait'});
                                            $('#paymentbtn').text('Please wait a few moments...').prop('disabled',true);
                                            errormessage.style.display = 'none';
                                        },
                                        success: function (get_quote_json) {
                                            if (get_quote_json.status == 200){
                                                const get_quote_result = get_quote_json.result;
                                                const get_collection_request_data = {
                                                    api_token               : api_auth_token,
                                                    quoteId                 : get_quote_result.quoteId,
                                                    serviceid               : get_quote_result.serviceid,
                                                    total_amount            : checkouttotalamount,
                                                    init_amount             : Math.ceil(checkoutamount),
                                                    account_number          : mobilewallet,
                                                    orderid                 : checkoutorderid,
                                                    customer_name           : customer_name,
                                                    customer_address        : customer_address,
                                                    currency                : currency,
                                                    _token                  : checkouttoken,
                                                }
                                                $.ajax({
                                                    type: 'post',
                                                    url:  collectionurl,
                                                    data: get_collection_request_data,
                                                    datatype: 'json',
                                                    beforeSend: function () {
                                                        $(document.body).css({'cursor' : 'wait'});
                                                        $('#paymentbtn').text('Transaction in progress...').prop('disabled',true);
                                                        errormessage.style.display = 'none';
                                                    },
                                                    success: function (collection_json) {
                                                        if (collection_json.status == 200){
                                                            const result_collection = collection_json.result
                                                            transactionref          = result_collection.transaction_ref;
                                                            if(result_collection.serviceid === "30056"){
                                                                $('#codevalidation').text('#150*50#');
                                                            }else{
                                                                $('#codevalidation').text('*126#');
                                                            }
                                                            setTimeout(() => paymentTerminate(timercheckstatus), timerbeforecheckstatus);
                                                            step1payment.style.display = 'none';
                                                            step2payment.style.display = 'block';
                                                            step3payment.style.display = 'none';
                                                            window.scrollTo({'top':0, 'behavior':'smooth'});
                                                        }else{
                                                            window.scrollTo({'top':0, 'behavior':'smooth'});
                                                            errormessage.style.display = 'block';
                                                            $('#error_message').text(collection_json.message).css('color', 'red');
                                                            $('#paymentbtn').text(btntransaction).prop('disabled',false);
                                                            loader.style.display = 'none';
                                                            $(document.body).css({'cursor' : 'default'});
                                                            form.find('*').prop('disabled', true);
                                                        }
                                                    },
                                                    complete: function () {
                                                        $(document.body).css({'cursor' : 'default'});
                                                    },
                                                    error: function(jqXHR, textStatus, errorThrown){}
                                                });
                                            }else{
                                                window.scrollTo({'top':0, 'behavior':'smooth'});
                                                errormessage.style.display = 'block';
                                                $('#error_message').text(get_quote_json.message).css('color', 'red');
                                                $(document.body).css({'cursor' : 'default'});
                                                $('#paymentbtn').text(btntransaction).prop('disabled',false);
                                                loader.style.display = 'none';
                                            }
                                        },
                                        complete: function () {
                                            $(document.body).css({'cursor' : 'default'});
                                        },
                                        error: function(jqXHR, textStatus, errorThrown){}
                                    });
                                }else{
                                    window.scrollTo({'top':0, 'behavior':'smooth'});
                                    errormessage.style.display = 'block';
                                    $('#error_message').text(json_cashout.message).css('color', 'red');
                                    $(document.body).css({'cursor' : 'default'});
                                    $('#paymentbtn').text(btntransaction).prop('disabled',false);
                                    loader.style.display = 'none';
                                }
                            },
                            complete: function () {
                                $(document.body).css({'cursor' : 'default'});
                            },
                            error: function(jqXHR, textStatus, errorThrown){}
                        });
                    }
                    if(servicecode === '10056'){
                        const visa_token_data = {
                            api_token               : api_auth_token,
                            serviceid               : servicecode,
                            _token                  : checkouttoken,
                        }
                        $.ajax({
                            type: 'post',
                            url:  visatokenurl,
                            data: visa_token_data,
                            datatype: 'json',
                            beforeSend: function () {
                                loader.style.display = 'block';
                                $(document.body).css({'cursor' : 'wait'});
                                $('#paymentbtn').text('en cours...').prop('disabled',true);
                                $('.error_message').css('display', 'none');
                                form.find('*').prop('disabled', true);
                            },
                            success: function (json_visa_collection) {
                                if (json_visa_collection.status == 200){
                                    const visa_token_result = json_visa_collection.result.access_token;
                                    visa_access_token = visa_token_result;
                                    const visa_token_data = {
                                        api_token               : api_auth_token,
                                        serviceid               : servicecode,
                                        access_token            : visa_access_token,
                                        total_amount            : checkouttotalamount,
                                        init_amount             : Math.ceil(checkoutamount),
                                        account_number          : mobilewallet,
                                        orderid                 : checkoutorderid,
                                        customer_name           : customer_name,
                                        customer_address        : customer_address,
                                        currency                : currency,
                                        _token                  : checkouttoken,
                                    }
                                    $.ajax({
                                        type: 'post',
                                        url:  visapaymentlinkurl,
                                        data: visa_token_data,
                                        datatype: 'json',
                                        beforeSend: function () {
                                            loader.style.display = 'block';
                                            $(document.body).css({'cursor' : 'wait'});
                                            $('#paymentbtn').text('en cours...').prop('disabled',true);
                                            $('.error_message').css('display', 'none');
                                            form.find('*').prop('disabled', true);
                                        },
                                        success: function (json_visa_collection) {
                                            if (json_visa_collection.status == 200){
                                                const visa_collection_result = json_visa_collection.result;
                                                transactionref  = visa_collection_result.transaction_ref
                                                visapaymentlink = visa_collection_result.payment_link
                                                setTimeout(() => paymentVisaTerminate(timercheckstatus), timerbeforecheckstatus);
                                                step1payment.style.display = 'none';
                                                step2payment.style.display = 'none';
                                                step3payment.style.display = 'block';
                                                window.scrollTo({'top':0, 'behavior':'smooth'});
                                                window.location.assign(visapaymentlink);
                                            }
                                        },
                                        complete: function () {
                                            $(document.body).css({'cursor' : 'default'});
                                        },
                                        error: function(jqXHR, textStatus, errorThrown){
                                            $(document.body).css({'cursor' : 'default'});
                                            $('#paymentbtn').text(btntransaction).prop('disabled',false);
                                        }
                                    });

                                }
                            },
                            complete: function () {
                                $(document.body).css({'cursor' : 'default'});
                            },
                            error: function(jqXHR, textStatus, errorThrown){
                                $(document.body).css({'cursor' : 'default'});
                                $('#paymentbtn').text(btntransaction).prop('disabled',false);
                            }
                        });
                    }
                }
            }else{
                window.scrollTo({'top':0, 'behavior':'smooth'});
                errormessage.style.display = 'block';
                $('#error_message').text(json_auth.message).css('color', 'red');
                $(document.body).css({'cursor' : 'default'});
                $('#paymentbtn').text(btntransaction).prop('disabled',false);
                loader.style.display = 'none';
            }
        },
        complete: function () {
            $(document.body).css({'cursor' : 'default'});
        },
        error: function(jqXHR, textStatus, errorThrown){
            $(document.body).css({'cursor' : 'default'});
            $('#paymentbtn').text(btntransaction).prop('disabled',false);
        }
    });
});

const paymentTerminate = () => {
    const data = {
        api_token           : api_auth_token,
        serviceid           : servicecode,
        transaction_ref     : transactionref,
        _token              : checkouttoken
    }
    $.ajax({
        type: 'post',
        url: collectionstatusurl,
        data: data,
        datatype: 'json',
        beforeSend: function () {
            loader.style.display = 'block';
            $(document.body).css({'cursor' : 'wait'});
            errormessage.style.display = 'none';
        },
        success: function (json) {
            if (json.status == 200){
                errormessage.style.display = 'block';
                $('#error_message').text(json.message).css('color', 'green');
                loader.style.display = 'none';
                setTimeout(
                    window.location.assign('/panel/courses/purchases')
                ,5000);
            }else if(json.status == 500){
                errormessage.style.display = 'block';
                $('#error_message').text(json.message).css('color', 'red');
                loader.style.display        = 'none'
                step1payment.style.display  = 'block';
                step2payment.style.display  = 'none';
                $('#paymentbtn').text(btntransaction).prop('disabled',false);
            }else if(json.status == 301){
                errormessage.style.display = 'block';
                $('#error_message').text(json.message).css('color', 'red');
            }else if(json.status == 302){
                loader.style.display = 'none'
                errormessage.style.display = 'block';
                $('#error_message').text("The operation failed, close the page and try again.").css('color', 'red');
                step1payment.style.display  = 'block';
                step2payment.style.display  = 'none';
                $('#paymentbtn').text(btntransaction).prop('disabled',false);
            }else{
                setTimeout(() => paymentTerminate(timercheckstatus), timerbeforecheckstatus);
            }
        },
        complete: function () {
            $(document.body).css({'cursor' : 'default'});
        },
        error: function(jqXHR, textStatus, errorThrown){}
    });
};

const paymentVisaTerminate = () => {
    const data = {
        api_token           : api_auth_token,
        access_token        : visa_access_token,
        serviceid           : servicecode,
        transaction_ref     : transactionref,
        _token              : checkouttoken
    }
    $.ajax({
        type: 'post',
        url: visacollectionstatusurl,
        data: data,
        datatype: 'json',
        beforeSend: function () {
            loader.style.display = 'block';
            $(document.body).css({'cursor' : 'wait'});
            errormessage.style.display = 'none';
        },
        success: function (json) {
            if (json.status == 200){
                errormessage.style.display = 'block';
                $('#error_message').text(json.message).css('color', 'green');
                setTimeout(
                    window.location.assign('/panel/courses/purchases')
                ,5000);
            }else if(json.status == 500){
                errormessage.style.display = 'block';
                $('#error_message').text(json.message).css('color', 'red');
                loader.style.display        = 'none'
                step1payment.style.display  = 'block';
                step2payment.style.display  = 'none';
                $('#paymentbtn').text(btntransaction).prop('disabled',false);
            }else if(json.status == 301){
                errormessage.style.display = 'block';
                $('#error_message').text(json.message).css('color', 'red');
            }else if(json.status == 302){
                loader.style.display = 'none'
                errormessage.style.display = 'block';
                $('#error_message').text("The operation failed, close the page and try again.").css('color', 'red');
                step1payment.style.display  = 'block';
                step2payment.style.display  = 'none';
                $('#paymentbtn').text(btntransaction).prop('disabled',false);
            }else{
                setTimeout(() => paymentVisaTerminate(timercheckstatus), timerbeforecheckstatus);
            }
        },
        complete: function () {
            $(document.body).css({'cursor' : 'default'});
        },
        error: function(jqXHR, textStatus, errorThrown){}
    });
};

const cancelPayment = () => {
    window.location.reload();
}
