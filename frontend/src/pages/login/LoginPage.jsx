import { useState } from "react";
import { loginUser } from '../../services/apiServices.js';
import { useNavigate } from "react-router-dom";
import Layout from '../../layout/Layout.jsx';
import '../../assets/styles/Forms.css'; 

import { getUserId, getUsername, isAuthenticated } from "../../utils/auth";

function LoginPage() {
    const [errores, setErrores] = useState([]);
    const navigate = useNavigate();

    const handleSubmit = async (e) => {
        e.preventDefault();

        const data = {
            usuario: e.target[0].value,
            password: e.target[1].value
        };

        const erroresDetectados = [];

        if (!data.usuario.trim()) {
            erroresDetectados.push("El nombre de usuario es obligatorio.");
        }
        if (!data.password.trim()) {
            erroresDetectados.push("La contraseña es obligatoria.");
        }

        if (erroresDetectados.length > 0) {
            setErrores(erroresDetectados);
            return;
        }

        try {
            const login = await loginUser(data);

            localStorage.setItem("token", login.data.token); 
            localStorage.setItem("id", getUserId());
            localStorage.setItem("usuario", getUsername());

            console.log(localStorage);
            
            console.log("Usuario logueado");
            setErrores([]);
            navigate("/");
        } catch (error) {
            if (error.response?.data?.error) {
                setErrores([error.response.data.error]);
            } else {
                setErrores(["Error al iniciar sesión."]);
            }
        }
    };

    const handleGoBack = () => {
        navigate("/"); 
    };

    return (
        <Layout>
            <div className="container d-flex justify-content-center align-items-center min-vh-100 py-5">
                <div className="left p-4 rounded shadow-lg mx-auto col-12 col-md-8 col-lg-6">
                    {errores.length > 0 && (
                        <div className="alert alert-danger" role="alert">
                            <ul className="mb-0 ps-3"> 
                                {errores.map((err, i) => (
                                    <li key={i}>{err}</li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <h2 className="title text-center mb-4">Por favor, inicia sesion</h2> 

                    <form className='form needs-validation' onSubmit={handleSubmit} noValidate>
                        <div className="mb-3"> 
                            <label htmlFor="usuarioInput" className="form-label">USUARIO</label>
                            <input
                                type="text"
                                className="form-control"
                                id="usuarioInput"
                                name="usuario"
                                placeholder="Ingresa tu usuario" 
                                required 
                            />
                        </div>

                        <div className="mb-4"> 
                            <label htmlFor="passwordInput" className="form-label">CONTRASEÑA</label>
                            <input
                                type="password"
                                className="form-control"
                                id="passwordInput"
                                name="password"
                                placeholder="Ingresa tu contraseña"
                                required
                            />
                        </div>

                        <div className="d-flex justify-content-between gap-2 flex-column flex-md-row mt-3">
                            <button type="submit" className="btn btn-primary flex-grow-1">Iniciar sesion</button>
                            <button type="button" className="btn btn-outline-secondary-custom flex-grow-1" onClick={handleGoBack}>
                                Volver al inicio
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Layout>
    );
}

export default LoginPage;
