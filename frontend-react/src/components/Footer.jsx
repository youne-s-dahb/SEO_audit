export default function Footer() {
  return (
    <footer className="footer">
      <div className="footer-inner">
        <span className="footer-brand">
          SEO<span className="dot">Audit</span>
        </span>
        <p className="footer-text">
          © {new Date().getFullYear()} SEOAudit.
        </p>
      </div>
    </footer>
  );
}