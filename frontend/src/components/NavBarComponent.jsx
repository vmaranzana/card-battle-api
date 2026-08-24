import { NavLink } from 'react-router-dom';
import '../assets/styles/NavBarComponent.css'; 

function NavBarComponent() {
  const token = localStorage.getItem('token');
  const nombre = localStorage.getItem('usuario');

  // Función para manejar el logout
  const handleLogout = () => {
    localStorage.clear();
    window.location.href = '/';
  };

  return (
    <nav className="main-nav"> 
      <ul className="nav-list"> 
        {token && (
          <>
            <li className="nav-item user-greeting"> 
              <span className="pokemon-link">Hola, {nombre}</span>
            </li>
          </>
        )}
        <li className="nav-item">
          <NavLink
            to="/"
            className={({ isActive }) => `pokemon-link ${isActive ? 'active' : ''}`}
            end 
          >
            INICIO
          </NavLink>
        </li>

        {!token ? (
          <>
            <li className="nav-item">
              <NavLink to="/registro" className={({ isActive }) => `pokemon-link ${isActive ? 'active' : ''}`}>CREAR CUENTA</NavLink>
            </li>
            <li className="nav-item">
              <NavLink to="/login" className={({ isActive }) => `btn access-button ${isActive ? 'active' : ''}`}> ACCEDER </NavLink>
            </li>
          </>
        ) : (
          <>
            <li className="nav-item">
              <NavLink to="/mis-mazos" className={({ isActive }) => `pokemon-link ${isActive ? 'active' : ''}`}>MIS MAZOS</NavLink>
            </li>
            <li className="nav-item">
              <NavLink to="/editar" className={({ isActive }) => `pokemon-link ${isActive ? 'active' : ''}`}>EDITAR USUARIO</NavLink>
            </li>
            <li className="nav-item">
              <button
                className="logout-button" 
                onClick={handleLogout} 
                title="Cerrar sesión" 
              >
                {/* Icono de Bootstrap */}
                <i className="bi bi-box-arrow-right"></i> 
              </button>
            </li>
          </>
        )}
      </ul>
    </nav>
  );
}

export default NavBarComponent;
