
const loader = document.querySelector('.loader');

function showLoader() {
    loader.classList.add('show');
   
}
new window.Flash

/* function page() {
    
}
async function showPosts() { 
    const posts = await getPosts();
    posts.forEach((post) => {
        postContainer.innerHTML+=`
        <div class="post">
            <div class="number">1</div>
            <div class="post-info">
                <h2 class="post-title">
                    Lorem ipsum dolor sit amet consectetur, adipisicing elit. Ad rem aperiam veritatis necessitatibus natus unde.
                </h2>
                <p class="post-body">
                    Lorem ipsum, dolor sit amet consectetur 
                    adipisicing elit. Odio hic natus dicta veniam omnis. 
                    Error repudiandae debitis facere obcaecati amet totam voluptate 
                    earum exercitationem corporis 
                </p>
            </div>
        </div>
        ,
        `
    });
}
async function getPosts() {
    let post;
    await fetch(url)
    //.then(resp=> console.log(resp))
     .then((resp) => resp.json())
     .then(data=>{
         post = data;
     });
     return post;
       
} */
window.addEventListener('scroll',function () {
    const {clientHeight,scrollTop,scrollHeight} = document.documentElement;
  
    if (scrollTop + clientHeight >= scrollHeight - 1510) {
      /*   this.showLoader();
        this.alert(true) */
    }
   
})