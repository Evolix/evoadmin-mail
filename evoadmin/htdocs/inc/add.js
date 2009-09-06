<script language="JavaScript" type="text/javascript">
<!--

function submit_add()
{
    if ((typeof(document.add.uid) != "undefined" ) && (document.add.uid.value == "")) {
        alert('Veuillez entre un Login.');
        document.add.uid.focus();
        return false;
    } else if (document.add.cn.value == "") {
        alert('Veuillez entrer le Prenom Nom.');
        document.add.cn.focus();
        return false;
    } else if (document.add.sn.value == "") {
        alert('Veuillez entrer un Nom.');
        document.add.sn.focus();
        return false;
    } else if (document.add.pass1.value != document.add.pass2.value) {
        alert('Erreur, dans la vérification du mot de passe.');
        document.add.pass1.focus();
        return false;
    } else if (document.add.smbgroup.value == "") {
        alert('Veuillez selectionner le groupe.');
        document.add.smbgroup.focus();
        return false;
    } else {
        return true;
    }
}
//-->

</script>

