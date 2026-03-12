pipeline {
    agent any

    options {
        skipDefaultCheckout()
    }

    environment {
        APP_NAME        = 'egov-backend'
        HARBOR_REGISTRY = '172.18.1.51:30500'
        HARBOR_PROJECT  = 'aadrika'
        IMAGE           = "${HARBOR_REGISTRY}/${HARBOR_PROJECT}/${APP_NAME}"
        CONFIG_REPO     = 'aadrika123/gitops-config'
    }

    stages {

        stage('Checkout') {
            steps {
                script {
                    def scmVars = checkout scm
                    def shortCommit = scmVars.GIT_COMMIT?.take(7) ?: 'unknown'
                    env.IMAGE_TAG = "${BUILD_NUMBER}-${shortCommit}"
                }
                echo "Branch: ${env.BRANCH_NAME} | Tag: ${env.IMAGE_TAG}"
            }
        }

        stage('Build Docker Image') {
            when {
                anyOf {
                    branch 'main'
                    branch 'master'
                    branch 'staging'
                    branch 'dev'
                }
            }
            steps {
                sh "docker build -t ${IMAGE}:${env.IMAGE_TAG} ."
            }
        }

        stage('Push to Harbor') {
            when {
                anyOf {
                    branch 'main'
                    branch 'master'
                    branch 'staging'
                    branch 'dev'
                }
            }
            steps {
                withCredentials([usernamePassword(
                    credentialsId: 'harbor-credentials',
                    usernameVariable: 'HARBOR_USER',
                    passwordVariable: 'HARBOR_PASS'
                )]) {
                    sh """
                        echo "\${HARBOR_PASS}" | docker login ${HARBOR_REGISTRY} -u "\${HARBOR_USER}" --password-stdin
                        docker push ${IMAGE}:${env.IMAGE_TAG}
                        docker logout ${HARBOR_REGISTRY}
                    """
                }
            }
        }

        stage('Update K8s Manifests') {
            when {
                anyOf {
                    branch 'main'
                    branch 'master'
                    branch 'staging'
                    branch 'dev'
                }
            }
            steps {
                dir('gitops-config') {
                    git url: "https://github.com/${CONFIG_REPO}.git",
                        branch: 'main',
                        credentialsId: 'github-credentials'

                    script {
                        def overlay = (env.BRANCH_NAME == 'main' || env.BRANCH_NAME == 'master') ? 'production' : 'staging'

                        dir("apps/${APP_NAME}/${overlay}") {
                            sh "kustomize edit set image ${IMAGE}=${IMAGE}:${env.IMAGE_TAG}"
                        }

                        env.DEPLOY_MSG = "deploy: ${APP_NAME}:${env.IMAGE_TAG} to ${overlay}"
                        env.PUSH_URL = "https://github.com/${CONFIG_REPO}.git"

                        withCredentials([usernamePassword(
                            credentialsId: 'github-credentials',
                            usernameVariable: 'GIT_USER',
                            passwordVariable: 'GIT_TOKEN'
                        )]) {
                            sh '''
                                git config user.email "jenkins@aadrikaenterprises.com"
                                git config user.name "Jenkins CI"
                                git add .
                                git commit -m "${DEPLOY_MSG}"

                                git remote set-url origin "https://${GIT_USER}:${GIT_TOKEN}@github.com/${CONFIG_REPO}.git"
                                for i in 1 2 3; do
                                    git pull --rebase origin main && \
                                    git push origin main && break
                                    echo "Push failed (attempt $i/3), retrying..."
                                    sleep 2
                                done
                            '''
                        }
                    }
                }
            }
        }
    }

    post {
        success {
            echo "✅ ${APP_NAME}:${env.IMAGE_TAG ?: 'none'} pushed — ArgoCD will deploy to ${env.BRANCH_NAME}"
        }
        failure {
            echo "❌ Pipeline failed for ${APP_NAME} on ${env.BRANCH_NAME}"
        }
        always {
            sh "docker rmi ${IMAGE}:${env.IMAGE_TAG ?: 'none'} || true"
            cleanWs()
        }
    }
}
