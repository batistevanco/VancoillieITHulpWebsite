// Intersection Observer voor animatie-effect op kaarten
const cards = document.querySelectorAll('.skillsCard');
const observer = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if(entry.isIntersecting) {
      entry.target.classList.add('visible');
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.2 });
cards.forEach(card => observer.observe(card));

// Back to top knop gedrag
const backToTopButton = document.getElementById('backToTop');
window.addEventListener('scroll', () => {
  backToTopButton.style.display = window.pageYOffset > 300 ? 'block' : 'none';
});
backToTopButton.addEventListener('click', () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
});

document.querySelector('.menu-toggle').addEventListener('click', function() {
  document.querySelector('.navbar ul').classList.toggle('active');
});

document.querySelectorAll('.navbar a').forEach(link => {
  link.addEventListener('click', function() {
      document.querySelector('.navbar ul').classList.remove('active');
  });
});
