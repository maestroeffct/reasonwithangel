<!-- Modale Payment -->
<div class="modal fade" id="openPayment" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <img class="logomodal" src="" alt="" id="afpmanager_logo">
                <h5 class="modal-title  fw-bold" id="addUserLabel">Make your payment securely</h5>
                <button type="button" onclick="cancelPayment()" class="btn-closemodal" aria-label="Close">X</button>
            </div>
            <div class="loadermodal" id="loadermodal"></div>
            <div class="error_message" id="error_message"></div>
            <div class="modal-body">
                <div class="row">
                    <div class="formcontainer" id="step1payment">
                        <form id="requestPayment" enctype="multipart/form-data" method="post">
                            @csrf
                            <input type="hidden" id="authapiurl" value="{{ route('auth-api-payment') }}">
                            <input type="hidden" id="commissionurl" value="{{ route('get-commission') }}">
                            <input type="hidden" id="cashouturl" value="{{ route('cash-out-request') }}">
                            <input type="hidden" id="getquoteurl" value="{{ route('get-quote-request') }}">
                            <input type="hidden" id="collectionurl" value="{{ route('collection-request') }}">
                            <input type="hidden" id="collectionstatusurl" value="{{ route('transaction-statement') }}">

                            <input type="hidden" id="visatokenurl" value="{{ route('get-visa-token') }}">
                            <input type="hidden" id="visapaymentlinkurl" value="{{ route('visa-collection-link') }}">
                            <input type="hidden" id="visacollectionstatusurl" value="{{ route('visa-collection-status') }}">

                            <div class="row blockformitem">
                                <div class="col-lg-12 col-md-12 col-xs-12">
                                    <p>Choose the payment method</p>
                                    <select id="paymentMethodOption" name="servicecode"
                                        class="form-control"
                                        onchange="getCommissionPrice(this)" required>
                                        <option value="">Selectionner</option>
                                        @if (currency() == 'XAF')
                                            <option value="30056">Mobile money Orange Cameroon</option>
                                            <option value="20056">Mobile money Mtn Cameroon</option>
                                        @endif
                                        <option value="10056">Visa card / Mastercard</option>
                                    </select>
                                    <hr>
                                </div>
                            </div>
                            <div id="transactionfeeinfo">
                                <h4>Transaction summary (In FCFA)</h4>
                                <hr>
                                <div class="itemtransinfo">
                                    <div class="row">
                                        <div class="col-lg-8 labelinfotransitem">
                                            <h5>Initial amount</h5>
                                        </div>
                                        <div class="col-lg-4 valueinfotransitem">
                                            <h6><span id="initialprice">0</span></h6>
                                        </div>
                                    </div>
                                </div>
                                <hr>

                                <div class="itemtransinfo">
                                    <div class="row">
                                        <div class="col-lg-8 labelinfotransitem">
                                            <h5>Transaction fees</h5>
                                        </div>
                                        <div class="col-lg-4 valueinfotransitem">
                                            <h6><span id="commissionamount">0</span></h6>
                                        </div>
                                    </div>
                                </div>
                                <hr>

                                <div class="itemtransinfo">
                                    <div class="row">
                                        <div class="col-lg-8 labelinfotransitem totalrecaplabel">
                                            <h5>Total to pay</h5>
                                        </div>
                                        <div class="col-lg-4 valueinfotransitem totalrecapvalue">
                                            <h6><span id="finalamount">0</span></h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row blockformitem">
                                <div class="col-lg-12 col-md-12 col-xs-12">
                                    <p>Phone number</p>
                                    <div class="form-group label-floating">
                                        <input type="number" class="form-control" name="mobilewallet" required placeholder="Phone... " />
                                        <hr>
                                    </div>
                                </div>
                            </div>
                            <div class="row blockformitem">
                                <div class="col-lg-12 col-md-12 col-xs-12">
                                    <p>Name linked to MOMO/VISA card account</p>
                                    <div class="form-group label-floating">
                                        <input class="form-control"  name="customer_name" required placeholder="Name... " />
                                        <hr>
                                    </div>
                                </div>
                            </div>
                            <div class="row blockformitem">
                                <div class="col-lg-12 col-md-12 col-xs-12">
                                    <p>Your address</p>
                                    <div class="form-group label-floating">
                                        <input class="form-control"  name="customer_address" required placeholder="Address... " />
                                        <hr>
                                    </div>
                                </div>
                            </div>
                            <div class="modalsubmitbloc">
                                <button class="btn initpaymentbtn" type="submit" id="paymentbtn">Make the payment</button>
                            </div>
                        </form>
                    </div>

                    <div class="formcontainer" id="step2payment">
                        <div>
                            <div class="validationtextpaymentcont">
                                <p>
                                    Please enter the following code on your phone to validate the payment. <span id="codevalidation"></span> <br>
                                </p>
                                <hr>
                            </div>
                            <div class="waitingpaymentloader">
                                <span id="waitingtext">Pending validation....</span>
                            </div>
                        </div>
                    </div>

                    <div class="formcontainer" id="step3payment">
                        <div>
                            <div class="validationtextpaymentcont">
                                <p>
                                    Please confirm your payment. <br>
                                </p>
                                <hr>
                            </div>
                            <div class="waitingpaymentloader">
                                <span id="waitingtext">Pending validation....</span>
                            </div>
                        </div>
                    </div>

                    <div class="companysigned col-lg-12">
                        <span class="afptext">© Afpmanager payment</span> <br><br>
                        <span class="certifytect">Certified by Poly-H Technology</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
