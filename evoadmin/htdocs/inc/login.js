<script language="JavaScript" type="text/javascript">
<!--

function submit_login()
{
    if (document.auth.login.value == "") {
        alert('Veuillez entrer votre nom de connexion et votre mot de passe');
        document.auth.login.focus();
        return false;
    } else if (document.auth.password.value == "") {
        alert('Veuillez entrer votre nom de connexion et votre mot de passe');
        document.auth.password.focus();
        return false;
    } else {
        return true;
    }
}
//-->

</script>

