
</div>
<footer class="site-footer">
	<div class="container">
    	<p>
	    <span>Evolix</span>
        <br /><strong>Hébergement et Infogérance Open Source</strong>
	</div>
</footer>

<script>
	$(document).ready(function() {
	    $('.table').DataTable({
       		"language": {
			    "emptyTable":     "Pas de données disponibles",
			    "info":           "Entrées _START_ à _END_ sur _TOTAL_ entries",
			    "infoEmpty":      "Entrées 0 à 0 sur 0",
			    "infoFiltered":   "(filtré sur un total de _MAX_ entrées)",
			    "infoPostFix":    "",
			    "thousands":      ",",
			    "lengthMenu":     "Montrer _MENU_ entrées",
			    "loadingRecords": "Chargement...",
			    "processing":     "Travail en cours...",
			    "search":         "Recherche : ",
			    "zeroRecords":    "Pas de resultat",
			    "paginate": {
			        "first":      "Première",
			        "last":       "Dernière",
			        "next":       "Suivante",
			        "previous":   "Précédente"
			    }
			},
			"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "toutes les"] ]
    	});
	} );
</script>

</body>
</html>
