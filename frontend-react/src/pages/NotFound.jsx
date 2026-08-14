import { Link } from "react-router-dom";

export default function NotFound() {
  return (
    <div className="home-page">
      <div className="home-card">
        <span className="eyebrow">404</span>
        <h1>Not Found</h1>
        <p>
          <Link to="/">Home</Link>
        </p>
      </div>
    </div>
  );
}
