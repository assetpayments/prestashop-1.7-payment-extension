{l s='Processing request...' mod='assetpayments'}

<form id="checkout" method="post" action="{$url}" accept-charset="utf-8">

		<input type="hidden" id="data" name="data" value="{$fields}" />

</form>
<script>
    window.onload = function() {
        document.getElementById('checkout').submit();
};
</script>
