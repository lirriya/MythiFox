const wrapper = document.querySelector('.wrapper');
const btnPopup = document.querySelector('.btnLogin-popup');
const closeBtn = document.querySelector('.close');
const downButton = document.getElementById('arrow');
const dropdownMenu = document.querySelector('.navigation .dropdown');


btnPopup.addEventListener('click', ()=> {
    wrapper.classList.add('active-popup');
});

closeBtn.addEventListener('click', ()=> {
    wrapper.classList.remove('active-popup');
});

downButton.addEventListener('click', () => {
  dropdownMenu.classList.toggle('show');
});
