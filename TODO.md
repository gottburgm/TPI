Améliorations à venir :
  
  - Site web central
    
    [+] Ajout de "Waypoints" au nombre de 10 (par exemple), peu importe le nombre de positions constituants le trajet saisit par
        l'utilisateur.
    
  - Site web Raspberry
  
      [+] Ajout de l'attribut (colonne) "synchronise" dans la table tblBus, pour que, lors de la création du bus par le technicien,
          même si la centrale n'est pas accessible, le bus soit enregistré dans celle-ci dès qu'elle le sera.
      
      [+] Ajout d'une tâche automatique qui, si le bus n'a pas été enregistré dans la centrale,
          s'executera toutes les secondes pour tester l'accessibilité de la centrale et y enregistrer le bus si elle l'est.
          Puis, changera la colonne "synchronise" de 0 à 1 et supprimera la tâche. 
