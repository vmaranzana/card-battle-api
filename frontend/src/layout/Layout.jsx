import HeaderComponent from "../components/HeaderComponent";
import FooterComponent from "../components/FooterComponent";
import '../assets/styles/index.css';

function Layout({ children }) {
  return (
    <div className="layout-grid">
      <HeaderComponent />
      <main className="main-content">
        {children}
      </main>
      <FooterComponent />
    </div>
  );
}

export default Layout;
