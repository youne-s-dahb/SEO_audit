import { Link } from "react-router-dom";

export default function NotFound() {
  return (
    <div className="home-page">
      <div className="home-card">
        <span className="eyebrow">404</span>
        <h1>Had page mal9ynahach</h1>
        <p>
          <Link to="/">Rje3 l home</Link>
        </p>
      </div>
    </div>
  );
}
