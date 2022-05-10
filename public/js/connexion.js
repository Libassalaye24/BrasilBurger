/* const form = document.getElementById('form');
 */const form = document.querySelector('form');

 const email = document.getElementById('inputEmail');
 const password = document.getElementById('inputPassword');



 let isValid = [];
 //Functions-------------------------------------------------------------
 function showError(input, message) {//Afficher les messages d'erreur
     const formControl = input.parentElement;
     formControl.className = 'form-control error';
     const small = formControl.querySelector('.invalid-feedback');
     small.innerText = message;
     isValid.push(false);
 }
 //
 function showSuccess(input) {
     const formControl = input.parentElement;
     formControl.className = 'form-control success';
    
     isValid.push(true);
 }
 //
 /* function checknbrEtage(input) {//Tester si l'nbrEtage est valide :  javascript : valid nbrEtage
     const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
 
     if (re.test(input.value.trim().toLowerCase())) {
         showSuccess(input);
     } else {
         showError(input,`nbrEtage is not valid!`);
     }
 } */
 //
 function checkRequired(inputArray){// Tester si les champs ne sont pas vides
     var bool =false;
     inputArray.forEach(input => {
         if (input.value.trim() === '') {
             showError(input,`Ce champ est obligatoire`);
          }else if(input.value === '0'){
              showError(input,`Ce champ est obligatoire`);
          }else{
              showSuccess(input);
             
          }
     });
       
 
 }
 //
 function getFieldName(input) {//Retour le nom de chaque input en se basant sur son id
     return input.id.charAt(0).toUpperCase() + input.id.slice(1);
 }
 //
 function checkLength(input, min, max) {//Tester la longueur de la valeur  d'un input
     if(input.value.length < min){
         showError(input, `Ce champ doit contenir au moins ${min} caractéres!`)
     }else if(input.value.length > max){
         showError(input, `Ce champ ne doit contenir au plus ${max} caractéres !`);
         console.log(input.value.length)
     }else{
         showSuccess(input);
         
     }
 }
 // 
 function checkNameStringMatch(input) {
     const  regex="^[A-Za-z]{1,20}, [A-Za-z]{1,20}, [A-Za-z]{1,20}";
     var bool = true;
     if (regex.test(input.value.trim())) {
         showSuccess(input);
     }else{
         showError(input,"le nom est invalide!!");
         bool = false;
     }
     return bool;
 }
 function CheckNumberMatch(input) {
    // const rg = new RegExp('^[0-9]+$');
    const reg = /^(33|77|78|75|76)[0-9]{7}/;
     if (!reg.test(input.value.trim())) {
         showError(input,"le Numero est invalide!!");
     }else{
         showSuccess(input);
         ;
     }
 
 }
 //
 function checkPasswordMatch(input1, input2) {
     if (input1.value !== input2.value) {
         showError(input2, 'Passwords do not match!');
     }
 }
 
 
 function checkEmail(input) {//Tester si l'email est valide :  javascript : valid email
    var bool = false;
     if(!isValidEmail(input.value)){
         showError(input,"L'email n'est pas valide!");
     }else{
         showSuccess(input);
         ;
     } 
}

function CheckEle(ele){
    if (ele.checked == false) {
        
    }
}


function isValidEmail(email) {//Tester si l'email est valide
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

 
 
 //Even listeners--------------------------------------------------------
 form.addEventListener('submit',function(e){    
   // var nb = CheckNumberMatch(nbrEtage);
        isValid = [];
         checkRequired([email,password]);
         checkLength(password,5,20);
         checkEmail(email);
        
     for (let i = 0; i < isValid.length; i++) {
         if (isValid[i]  == false) {
            e.preventDefault();
         }
         
     }
    
  
 
 
 
  //  var str = checkNameStringMatch(nomPavillon);
  //  var lg = checkLength(nomPavillon,4,20);
 
 
 
 //document.getElementById('button').onsubmit();  
 
 
    /* if (!str) {
         e.preventDefault(); 
    }
    if (!lg) {
     e.preventDefault();   
    }
    if (!nb) {
     e.preventDefault();   
    } */
     //
 
   
 
 
     
   /*   function isValidnbrEtage(nbrEtage) {//Tester si l'nbrEtage est valide
     const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
     return re.test(String(nbrEtage).toLowerCase());
 }
     if (nomPavillon.value === '') {
        showError(nomPavillon,'nomPavillon is required!'); 
     }else{
         showSuccess(nomPavillon);
     }
 
     if (nbrEtage.value === '') {
        showError(nbrEtage,'nbrEtage is required!'); 
     }else if(!isValidnbrEtage(nbrEtage.value)){
         showError(nbrEtage,'nbrEtage is not valid!');
     }else{
         showSuccess(nbrEtage);
     } */
 
   /*   if (password.value === '') {
        showError(password,'password is required!'); 
     }else{
         showSuccess(password);
     }
 
     if (password2.value === '') {
        showError(password2,'Password 2 is required!'); 
     }else{
         showSuccess(password2);
     } */
 });