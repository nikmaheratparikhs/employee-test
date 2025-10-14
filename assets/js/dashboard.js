document.addEventListener('DOMContentLoaded', () => {
  const cards = document.querySelectorAll('.card-hover');
  cards.forEach((c, i) => {
    c.style.opacity = '0';
    c.style.transform = 'translateY(8px)';
    setTimeout(() => {
      c.style.transition = 'opacity .4s ease, transform .4s ease';
      c.style.opacity = '1';
      c.style.transform = 'translateY(0)';
    }, 60 * i);
  });
});
